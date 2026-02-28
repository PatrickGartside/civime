#!/usr/bin/env bash
#
# Phase 3.1 — API Integration Test Suite
#
# Tests all Access100 API endpoints against the local Docker instance.
# Verifies response codes, JSON structure, field names, data types,
# and the full subscription lifecycle.
#
# Usage:
#   chmod +x tests/test-api-integration.sh
#   ./tests/test-api-integration.sh
#
# Requirements:
#   - Access100 API Docker stack running on port 8082
#   - curl and jq installed
#   - Test data seeded (MTG-001, MTG-002, MTG-003 meetings)

set -uo pipefail

API="http://localhost:8082/api/v1"
KEY="changeme-generate-a-64-char-random-key"

PASS=0
FAIL=0
ERRORS=()

# ── Helpers ──────────────────────────────────────────────────────────────────

green()  { printf "\033[32m%s\033[0m\n" "$1"; }
red()    { printf "\033[31m%s\033[0m\n" "$1"; }
yellow() { printf "\033[33m%s\033[0m\n" "$1"; }
bold()   { printf "\033[1m%s\033[0m\n" "$1"; }

pass() {
    PASS=$((PASS + 1))
    green "  PASS: $1"
}

fail() {
    FAIL=$((FAIL + 1))
    red "  FAIL: $1"
    ERRORS+=("$1")
}

assert_eq() {
    local actual="$1" expected="$2" label="$3"
    if [[ "$actual" == "$expected" ]]; then
        pass "$label"
    else
        fail "$label (expected '$expected', got '$actual')"
    fi
}

assert_not_empty() {
    local actual="$1" label="$2"
    if [[ -n "$actual" && "$actual" != "null" ]]; then
        pass "$label"
    else
        fail "$label (was empty or null)"
    fi
}

assert_match() {
    local actual="$1" pattern="$2" label="$3"
    if [[ "$actual" =~ $pattern ]]; then
        pass "$label"
    else
        fail "$label (expected match '$pattern', got '$actual')"
    fi
}

# Authenticated GET
aget() { curl -sf -H "X-API-Key: $KEY" "$API$1" 2>/dev/null; }

# Authenticated GET with status code
aget_status() { curl -so /dev/null -w "%{http_code}" -H "X-API-Key: $KEY" "$API$1" 2>/dev/null; }

# Unauthenticated GET
uget() { curl -sf "$API$1" 2>/dev/null; }

# Unauthenticated GET with status code
uget_status() { curl -so /dev/null -w "%{http_code}" "$API$1" 2>/dev/null; }

# Authenticated POST
apost() { curl -sf -X POST -H "X-API-Key: $KEY" -H "Content-Type: application/json" -d "$2" "$API$1" 2>/dev/null; }

# Authenticated POST with status code
apost_status() { curl -so /dev/null -w "%{http_code}" -X POST -H "X-API-Key: $KEY" -H "Content-Type: application/json" -d "$2" "$API$1" 2>/dev/null; }

# Authenticated PATCH
apatch() { curl -sf -X PATCH -H "X-API-Key: $KEY" -H "Content-Type: application/json" -d "$2" "$API$1" 2>/dev/null; }

# Authenticated PUT
aput() { curl -sf -X PUT -H "X-API-Key: $KEY" -H "Content-Type: application/json" -d "$2" "$API$1" 2>/dev/null; }

# Authenticated DELETE
adel() { curl -sf -X DELETE -H "X-API-Key: $KEY" "$API$1" 2>/dev/null; }

# Authenticated DELETE with status
adel_status() { curl -so /dev/null -w "%{http_code}" -X DELETE -H "X-API-Key: $KEY" "$API$1" 2>/dev/null; }

# ── Preflight ────────────────────────────────────────────────────────────────

bold "=== Phase 3.1 — API Integration Tests ==="
echo ""

# Check API is reachable
if ! curl -sf "$API/health" > /dev/null 2>&1; then
    red "ERROR: API not reachable at $API/health"
    red "Make sure the Access100 Docker stack is running on port 8082."
    exit 1
fi
green "API is reachable."
echo ""

# ═════════════════════════════════════════════════════════════════════════════
bold "── 1. Health Endpoint (public, no auth) ──"
# ═════════════════════════════════════════════════════════════════════════════

HEALTH=$(uget "/health")
assert_eq "$(echo "$HEALTH" | jq -r '.data.status')" "ok" "Health status is 'ok'"
assert_eq "$(echo "$HEALTH" | jq -r '.data.database')" "connected" "Database is connected"
assert_eq "$(echo "$HEALTH" | jq -r '.data.version')" "v1" "API version is v1"
assert_not_empty "$(echo "$HEALTH" | jq -r '.data.meetings_count')" "meetings_count present"
assert_not_empty "$(echo "$HEALTH" | jq -r '.meta.timestamp')" "meta.timestamp present"
echo ""

# ═════════════════════════════════════════════════════════════════════════════
bold "── 2. Auth & Security ──"
# ═════════════════════════════════════════════════════════════════════════════

# No API key → 401
STATUS=$(curl -so /dev/null -w "%{http_code}" "$API/meetings" 2>/dev/null)
assert_eq "$STATUS" "401" "Meetings without API key returns 401"

# Wrong API key → 401
STATUS=$(curl -so /dev/null -w "%{http_code}" -H "X-API-Key: wrong-key" "$API/meetings" 2>/dev/null)
assert_eq "$STATUS" "401" "Meetings with wrong API key returns 401"

# Health is public (no key needed)
STATUS=$(uget_status "/health")
assert_eq "$STATUS" "200" "Health endpoint is public (no key needed)"

# Stats is public
STATUS=$(uget_status "/stats")
assert_eq "$STATUS" "200" "Stats endpoint is public (no key needed)"
echo ""

# ═════════════════════════════════════════════════════════════════════════════
bold "── 3. Meetings List ──"
# ═════════════════════════════════════════════════════════════════════════════

MEETINGS=$(aget "/meetings?limit=50")

# Response envelope
assert_not_empty "$(echo "$MEETINGS" | jq -r '.data')" "Response has 'data' field"
assert_not_empty "$(echo "$MEETINGS" | jq -r '.meta')" "Response has 'meta' field"
assert_not_empty "$(echo "$MEETINGS" | jq -r '.meta.total')" "meta.total present"
assert_not_empty "$(echo "$MEETINGS" | jq -r '.meta.limit')" "meta.limit present"

# Data shape — check first meeting
FIRST=$(echo "$MEETINGS" | jq '.data[0]')
assert_not_empty "$(echo "$FIRST" | jq -r '.state_id')" "meetings[0].state_id present"
assert_match "$(echo "$FIRST" | jq -r '.state_id')" "^MTG-" "state_id is a string (not 0)"
assert_not_empty "$(echo "$FIRST" | jq -r '.title')" "meetings[0].title present"
assert_match "$(echo "$FIRST" | jq -r '.meeting_date')" "^[0-9]{4}-[0-9]{2}-[0-9]{2}$" "meeting_date is YYYY-MM-DD"
assert_match "$(echo "$FIRST" | jq -r '.meeting_time')" "^[0-9]{2}:[0-9]{2}:" "meeting_time is HH:MM:SS"
assert_not_empty "$(echo "$FIRST" | jq -r '.location')" "meetings[0].location present"
assert_not_empty "$(echo "$FIRST" | jq -r '.status')" "meetings[0].status present"

# Nested council object
assert_not_empty "$(echo "$FIRST" | jq -r '.council.id')" "meetings[0].council.id present"
assert_not_empty "$(echo "$FIRST" | jq -r '.council.name')" "meetings[0].council.name present"

# Pagination
assert_eq "$(echo "$MEETINGS" | jq -r '.meta.limit')" "50" "Default limit is 50"
assert_eq "$(echo "$MEETINGS" | jq -r '.meta.offset')" "0" "Default offset is 0"

# Limit parameter
LIMITED=$(aget "/meetings?limit=1")
assert_eq "$(echo "$LIMITED" | jq '.data | length')" "1" "limit=1 returns 1 meeting"
assert_eq "$(echo "$LIMITED" | jq -r '.meta.has_more')" "true" "has_more=true when more exist"

# Council filter
FILTERED=$(aget "/meetings?council_id=1")
COUNCIL_IDS=$(echo "$FILTERED" | jq -c '[.data[].council.id] | unique')
assert_eq "$COUNCIL_IDS" "[1]" "council_id=1 filter returns only council 1"

# Date filter
FUTURE=$(aget "/meetings?date_from=2026-04-01")
DATES=$(echo "$FUTURE" | jq -r '[.data[].meeting_date] | sort | .[0]')
if [[ -n "$DATES" && "$DATES" != "null" ]]; then
    assert_match "$DATES" "^2026-0[4-9]" "date_from filter works (first date >= 2026-04)"
else
    pass "date_from filter works (no results for future date is valid)"
fi
echo ""

# ═════════════════════════════════════════════════════════════════════════════
bold "── 4. Meeting Detail ──"
# ═════════════════════════════════════════════════════════════════════════════

DETAIL=$(aget "/meetings/MTG-001")
DATA=$(echo "$DETAIL" | jq '.data')

assert_eq "$(echo "$DATA" | jq -r '.state_id')" "MTG-001" "Detail state_id matches"
assert_not_empty "$(echo "$DATA" | jq -r '.title')" "Detail has title"
assert_not_empty "$(echo "$DATA" | jq -r '.meeting_date')" "Detail has meeting_date"
assert_not_empty "$(echo "$DATA" | jq -r '.meeting_time')" "Detail has meeting_time"
assert_not_empty "$(echo "$DATA" | jq -r '.location')" "Detail has location"
assert_not_empty "$(echo "$DATA" | jq -r '.status')" "Detail has status"

# Detail-only fields (not in list)
echo "$DATA" | jq -e 'has("description")' > /dev/null 2>&1 && pass "Detail has description field" || fail "Detail missing description field"
echo "$DATA" | jq -e 'has("summary_text")' > /dev/null 2>&1 && pass "Detail has summary_text field" || fail "Detail missing summary_text field"
echo "$DATA" | jq -e 'has("zoom_link")' > /dev/null 2>&1 && pass "Detail has zoom_link field" || fail "Detail missing zoom_link field"
echo "$DATA" | jq -e 'has("detail_url")' > /dev/null 2>&1 && pass "Detail has detail_url field" || fail "Detail missing detail_url field"
echo "$DATA" | jq -e 'has("attachments")' > /dev/null 2>&1 && pass "Detail has attachments field" || fail "Detail missing attachments field"

# Council nested object in detail
assert_not_empty "$(echo "$DATA" | jq -r '.council.id')" "Detail has council.id"
assert_not_empty "$(echo "$DATA" | jq -r '.council.name')" "Detail has council.name"

# 404 for nonexistent meeting
STATUS=$(aget_status "/meetings/NONEXISTENT-999")
assert_eq "$STATUS" "404" "Nonexistent meeting returns 404"

# 400 for invalid ID
STATUS=$(aget_status "/meetings/../../etc/passwd")
assert_eq "$STATUS" "400" "Path traversal attempt returns 400"
echo ""

# ═════════════════════════════════════════════════════════════════════════════
bold "── 5. Meeting Summary ──"
# ═════════════════════════════════════════════════════════════════════════════

SUMMARY=$(aget "/meetings/MTG-001/summary")
assert_eq "$(echo "$SUMMARY" | jq -r '.data.state_id')" "MTG-001" "Summary state_id matches"
assert_not_empty "$(echo "$SUMMARY" | jq -r '.data.summary_text')" "Summary has summary_text"
assert_not_empty "$(echo "$SUMMARY" | jq -r '.data.council_name')" "Summary has council_name"

# 404 for meeting without summary
STATUS=$(aget_status "/meetings/MTG-002/summary")
# MTG-002 may or may not have a summary — accept 200 or 404
if [[ "$STATUS" == "200" || "$STATUS" == "404" ]]; then
    pass "Summary endpoint returns 200 or 404 appropriately"
else
    fail "Summary endpoint returned unexpected $STATUS"
fi
echo ""

# ═════════════════════════════════════════════════════════════════════════════
bold "── 6. Meeting ICS ──"
# ═════════════════════════════════════════════════════════════════════════════

ICS=$(curl -s -H "X-API-Key: $KEY" "$API/meetings/MTG-001/ics" 2>/dev/null)
echo "$ICS" | grep -q "BEGIN:VCALENDAR" && pass "ICS contains VCALENDAR" || fail "ICS missing VCALENDAR"
echo "$ICS" | grep -q "BEGIN:VEVENT" && pass "ICS contains VEVENT" || fail "ICS missing VEVENT"
echo "$ICS" | grep -q "DTSTART:" && pass "ICS contains DTSTART" || fail "ICS missing DTSTART"
echo "$ICS" | grep -q "DTEND:" && pass "ICS contains DTEND" || fail "ICS missing DTEND"
echo "$ICS" | grep -q "SUMMARY:" && pass "ICS contains SUMMARY" || fail "ICS missing SUMMARY"
echo "$ICS" | grep -q "LOCATION:" && pass "ICS contains LOCATION" || fail "ICS missing LOCATION"
echo "$ICS" | grep -q "MTG-001@access100.app" && pass "ICS UID uses state_id" || fail "ICS UID doesn't use state_id"

# Verify no PHP warnings in ICS output
if echo "$ICS" | grep -qi -E "<b>(Deprecated|Warning|Notice|Fatal)</b>"; then
    fail "ICS output contains PHP errors"
else
    pass "ICS output has no PHP errors"
fi

ICS_CT=$(curl -sD - -o /dev/null -H "X-API-Key: $KEY" "$API/meetings/MTG-001/ics" 2>/dev/null | grep -i "content-type" | tr -d '\r')
echo "$ICS_CT" | grep -qi "text/calendar" && pass "ICS Content-Type is text/calendar" || fail "ICS Content-Type wrong: $ICS_CT"
echo ""

# ═════════════════════════════════════════════════════════════════════════════
bold "── 7. Councils List ──"
# ═════════════════════════════════════════════════════════════════════════════

COUNCILS=$(aget "/councils")
assert_not_empty "$(echo "$COUNCILS" | jq -r '.data')" "Councils response has data"
assert_not_empty "$(echo "$COUNCILS" | jq -r '.meta.total')" "Councils meta.total present"

FIRST_C=$(echo "$COUNCILS" | jq '.data[0]')
assert_not_empty "$(echo "$FIRST_C" | jq -r '.id')" "councils[0].id present"
assert_not_empty "$(echo "$FIRST_C" | jq -r '.name')" "councils[0].name present"
echo "$FIRST_C" | jq -e 'has("upcoming_meeting_count")' > /dev/null 2>&1 && pass "councils[0] has upcoming_meeting_count" || fail "councils[0] missing upcoming_meeting_count"

# Keyword filter
FILTERED_C=$(aget "/councils?q=Education")
assert_eq "$(echo "$FILTERED_C" | jq '.data | length')" "1" "Council search for 'Education' returns 1 result"
assert_eq "$(echo "$FILTERED_C" | jq -r '.data[0].name')" "Board of Education" "Filtered council name matches"

# has_upcoming filter
HAS_UPCOMING=$(aget "/councils?has_upcoming=true")
COUNTS=$(echo "$HAS_UPCOMING" | jq '[.data[].upcoming_meeting_count] | min')
if [[ "$COUNTS" != "null" && "$COUNTS" -ge 1 ]]; then
    pass "has_upcoming=true only returns councils with meetings"
else
    # Could be empty if no upcoming meetings
    pass "has_upcoming filter accepted (may be empty)"
fi
echo ""

# ═════════════════════════════════════════════════════════════════════════════
bold "── 8. Council Detail ──"
# ═════════════════════════════════════════════════════════════════════════════

COUNCIL_D=$(aget "/councils/1")
assert_eq "$(echo "$COUNCIL_D" | jq -r '.data.id')" "1" "Council detail ID matches"
assert_not_empty "$(echo "$COUNCIL_D" | jq -r '.data.name')" "Council detail has name"
echo "$COUNCIL_D" | jq -e '.data | has("upcoming_meeting_count")' > /dev/null 2>&1 && pass "Council detail has upcoming_meeting_count" || fail "Council detail missing upcoming_meeting_count"
echo "$COUNCIL_D" | jq -e '.data | has("children")' > /dev/null 2>&1 && pass "Council detail has children array" || fail "Council detail missing children"

# 404 for nonexistent council
STATUS=$(aget_status "/councils/99999")
assert_eq "$STATUS" "404" "Nonexistent council returns 404"
echo ""

# ═════════════════════════════════════════════════════════════════════════════
bold "── 9. Council Meetings ──"
# ═════════════════════════════════════════════════════════════════════════════

CM=$(aget "/councils/1/meetings")
assert_not_empty "$(echo "$CM" | jq -r '.data')" "Council meetings has data"
assert_eq "$(echo "$CM" | jq -r '.meta.council_id')" "1" "Council meetings meta has council_id"
assert_not_empty "$(echo "$CM" | jq -r '.meta.council_name')" "Council meetings meta has council_name"

# Verify state_id is a string, not 0
if echo "$CM" | jq -e '.data | length > 0' > /dev/null 2>&1; then
    CM_SID=$(echo "$CM" | jq -r '.data[0].state_id')
    assert_match "$CM_SID" "^MTG-" "Council meetings state_id is a string"
else
    pass "Council meetings (no data to check, OK)"
fi
echo ""

# ═════════════════════════════════════════════════════════════════════════════
bold "── 10. Subscription Lifecycle ──"
# ═════════════════════════════════════════════════════════════════════════════

# 10a. Create subscription
yellow "  Creating test subscription..."
CREATE_BODY='{
    "email": "test-e2e@example.com",
    "channels": ["email"],
    "council_ids": [1],
    "frequency": "immediate",
    "source": "civime"
}'

CREATE_RESP=$(curl -s -X POST -H "X-API-Key: $KEY" -H "Content-Type: application/json" -d "$CREATE_BODY" "$API/subscriptions" -w "\n%{http_code}" 2>/dev/null)
CREATE_STATUS=$(echo "$CREATE_RESP" | tail -1)
CREATE_RESP=$(echo "$CREATE_RESP" | sed '$d')

assert_eq "$CREATE_STATUS" "201" "Create subscription returns 201"
USER_ID=$(echo "$CREATE_RESP" | jq -r '.data.user_id')
MANAGE_TOKEN=$(echo "$CREATE_RESP" | jq -r '.data.manage_token')
assert_not_empty "$USER_ID" "Create returns user_id"
assert_not_empty "$MANAGE_TOKEN" "Create returns manage_token"
assert_eq "$(echo "$CREATE_RESP" | jq -r '.data.status')" "pending_confirmation" "Status is pending_confirmation"
assert_eq "$(echo "$CREATE_RESP" | jq -r '.data.frequency')" "immediate" "Frequency matches"

# 10b. Validation — missing email and phone
BAD_STATUS=$(curl -so /dev/null -w "%{http_code}" -X POST -H "X-API-Key: $KEY" -H "Content-Type: application/json" -d '{"channels":["email"],"council_ids":[1]}' "$API/subscriptions" 2>/dev/null)
assert_eq "$BAD_STATUS" "400" "Missing email+phone returns 400"

# Validation — bad email
BAD_STATUS=$(curl -so /dev/null -w "%{http_code}" -X POST -H "X-API-Key: $KEY" -H "Content-Type: application/json" -d '{"email":"not-an-email","channels":["email"],"council_ids":[1]}' "$API/subscriptions" 2>/dev/null)
assert_eq "$BAD_STATUS" "400" "Bad email format returns 400"

# Validation — no councils
BAD_STATUS=$(curl -so /dev/null -w "%{http_code}" -X POST -H "X-API-Key: $KEY" -H "Content-Type: application/json" -d '{"email":"test@example.com","channels":["email"],"council_ids":[]}' "$API/subscriptions" 2>/dev/null)
assert_eq "$BAD_STATUS" "400" "Empty council_ids returns 400"

# 10c. Get subscription (manage token auth)
if [[ -n "$USER_ID" && "$USER_ID" != "null" && -n "$MANAGE_TOKEN" && "$MANAGE_TOKEN" != "null" ]]; then
    GET_SUB=$(aget "/subscriptions/${USER_ID}?token=${MANAGE_TOKEN}")
    assert_eq "$(echo "$GET_SUB" | jq -r '.data.email')" "test-e2e@example.com" "Get subscription returns email"
    assert_not_empty "$(echo "$GET_SUB" | jq -r '.data.subscriptions')" "Get subscription has subscriptions array"

    # Wrong token → 401
    WRONG_STATUS=$(aget_status "/subscriptions/${USER_ID}?token=wrongtoken")
    assert_eq "$WRONG_STATUS" "401" "Wrong manage token returns 401"

    # Missing token → 401
    MISSING_STATUS=$(aget_status "/subscriptions/${USER_ID}")
    assert_eq "$MISSING_STATUS" "401" "Missing manage token returns 401"

    # 10d. Update subscription (PATCH)
    yellow "  Updating subscription frequency..."
    PATCH_RESP=$(apatch "/subscriptions/${USER_ID}?token=${MANAGE_TOKEN}" '{"frequency":"daily"}')
    assert_eq "$(echo "$PATCH_RESP" | jq -r '.data.status')" "updated" "PATCH returns status=updated"

    # Verify the change persisted
    VERIFY=$(aget "/subscriptions/${USER_ID}?token=${MANAGE_TOKEN}")
    FREQ=$(echo "$VERIFY" | jq -r '.data.subscriptions[0].frequency')
    assert_eq "$FREQ" "daily" "Frequency updated to daily"

    # 10e. Replace councils (PUT)
    yellow "  Replacing subscription councils..."
    PUT_RESP=$(aput "/subscriptions/${USER_ID}/councils?token=${MANAGE_TOKEN}" '{"council_ids":[1,2]}')
    assert_eq "$(echo "$PUT_RESP" | jq -r '.data.status')" "updated" "PUT councils returns status=updated"

    VERIFY2=$(aget "/subscriptions/${USER_ID}?token=${MANAGE_TOKEN}")
    SUB_COUNT=$(echo "$VERIFY2" | jq '.data.subscriptions | length')
    assert_eq "$SUB_COUNT" "2" "Now subscribed to 2 councils"

    # 10f. Delete (unsubscribe)
    yellow "  Unsubscribing..."
    DEL_RESP=$(adel "/subscriptions/${USER_ID}?token=${MANAGE_TOKEN}")
    assert_eq "$(echo "$DEL_RESP" | jq -r '.data.status')" "unsubscribed" "DELETE returns status=unsubscribed"

    # Verify all subscriptions deactivated
    VERIFY3=$(aget "/subscriptions/${USER_ID}?token=${MANAGE_TOKEN}")
    ACTIVE_COUNT=$(echo "$VERIFY3" | jq '[.data.subscriptions[] | select(.active == true)] | length')
    assert_eq "$ACTIVE_COUNT" "0" "All subscriptions deactivated after delete"
else
    fail "Skipping subscription lifecycle — user_id or manage_token missing"
fi
echo ""

# ═════════════════════════════════════════════════════════════════════════════
bold "── 11. Confirm & Unsubscribe Links ──"
# ═════════════════════════════════════════════════════════════════════════════

# Create a fresh subscription to test confirmation
CONFIRM_BODY='{
    "email": "test-confirm@example.com",
    "channels": ["email"],
    "council_ids": [1],
    "frequency": "immediate",
    "source": "civime"
}'
CONFIRM_RESP=$(curl -s -X POST -H "X-API-Key: $KEY" -H "Content-Type: application/json" -d "$CONFIRM_BODY" "$API/subscriptions")
CONFIRM_USER_ID=$(echo "$CONFIRM_RESP" | jq -r '.data.user_id')
CONFIRM_MANAGE=$(echo "$CONFIRM_RESP" | jq -r '.data.manage_token')

# Get the confirm token from the database
ROOTPW=$(grep MYSQL_ROOT_PASSWORD ~/dev/Access100/app\ website/.env | cut -d= -f2)
CONFIRM_TOKEN=$(docker exec appwebsite-db-1 mysql -u root -p"$ROOTPW" u325862315_access100 -sNe \
    "SELECT confirm_token FROM users WHERE email='test-confirm@example.com' LIMIT 1" 2>/dev/null)

if [[ -n "$CONFIRM_TOKEN" && "$CONFIRM_TOKEN" != "NULL" ]]; then
    # Confirm endpoint — capture headers + status in one call (token is consumed)
    CONFIRM_HEADERS=$(curl -sD - -o /dev/null -w "\nHTTP_STATUS:%{http_code}" "$API/subscriptions/confirm?token=$CONFIRM_TOKEN" 2>/dev/null)
    CONFIRM_STATUS=$(echo "$CONFIRM_HEADERS" | grep "HTTP_STATUS:" | sed 's/HTTP_STATUS://')
    CONFIRM_LOC=$(echo "$CONFIRM_HEADERS" | grep -i "^location:" | tr -d '\r')
    assert_eq "$CONFIRM_STATUS" "302" "Confirm token redirects with 302"
    if echo "$CONFIRM_LOC" | grep -qi "notifications/confirmed"; then
        pass "Confirm redirects to /notifications/confirmed"
    else
        fail "Confirm redirect wrong: $CONFIRM_LOC"
    fi

    # Verify confirmed_email is now TRUE
    CONFIRMED=$(docker exec appwebsite-db-1 mysql -u root -p"$ROOTPW" u325862315_access100 -sNe \
        "SELECT confirmed_email FROM users WHERE email='test-confirm@example.com' LIMIT 1" 2>/dev/null)
    assert_eq "$CONFIRMED" "1" "confirmed_email set to TRUE after confirmation"

    # Used token should fail (token was cleared)
    REUSE_STATUS=$(curl -so /dev/null -w "%{http_code}" "$API/subscriptions/confirm?token=$CONFIRM_TOKEN" 2>/dev/null)
    assert_eq "$REUSE_STATUS" "404" "Reused confirm token returns 404"
else
    fail "Could not retrieve confirm_token from database"
fi

# Unsubscribe link (uses manage token)
if [[ -n "$CONFIRM_MANAGE" && "$CONFIRM_MANAGE" != "null" ]]; then
    UNSUB_HEADERS=$(curl -sD - -o /dev/null -w "\nHTTP_STATUS:%{http_code}" "$API/subscriptions/unsubscribe?token=$CONFIRM_MANAGE" 2>/dev/null)
    UNSUB_STATUS=$(echo "$UNSUB_HEADERS" | grep "HTTP_STATUS:" | sed 's/HTTP_STATUS://')
    UNSUB_LOC=$(echo "$UNSUB_HEADERS" | grep -i "^location:" | tr -d '\r')
    assert_eq "$UNSUB_STATUS" "302" "Unsubscribe link redirects with 302"
    if echo "$UNSUB_LOC" | grep -qi "notifications/unsubscribed"; then
        pass "Unsubscribe redirects to /notifications/unsubscribed"
    else
        fail "Unsubscribe redirect wrong: $UNSUB_LOC"
    fi
fi

# Bad token
BAD_CONFIRM=$(curl -so /dev/null -w "%{http_code}" "$API/subscriptions/confirm?token=0000000000000000000000000000000000000000000000000000000000000000" 2>/dev/null)
assert_eq "$BAD_CONFIRM" "404" "Invalid confirm token returns 404"
echo ""

# ═════════════════════════════════════════════════════════════════════════════
bold "── 12. Stats Endpoint ──"
# ═════════════════════════════════════════════════════════════════════════════

STATS=$(uget "/stats")
assert_not_empty "$(echo "$STATS" | jq -r '.data')" "Stats has data"
echo ""

# ═════════════════════════════════════════════════════════════════════════════
bold "── 13. Error Handling ──"
# ═════════════════════════════════════════════════════════════════════════════

# 404 for unknown resource
STATUS=$(aget_status "/nonexistent")
assert_eq "$STATUS" "404" "Unknown resource returns 404"

# 405 for POST to meetings
STATUS=$(curl -so /dev/null -w "%{http_code}" -X POST -H "X-API-Key: $KEY" "$API/meetings" 2>/dev/null)
assert_eq "$STATUS" "405" "POST to meetings returns 405"

# 405 for POST to councils
STATUS=$(curl -so /dev/null -w "%{http_code}" -X POST -H "X-API-Key: $KEY" "$API/councils" 2>/dev/null)
assert_eq "$STATUS" "405" "POST to councils returns 405"

# Error response envelope
ERROR_RESP=$(curl -s "$API/meetings" 2>/dev/null)
echo "$ERROR_RESP" | jq -e '.error.code' > /dev/null 2>&1 && pass "Error has error.code" || fail "Error missing error.code"
echo "$ERROR_RESP" | jq -e '.error.message' > /dev/null 2>&1 && pass "Error has error.message" || fail "Error missing error.message"
echo ""

# ═════════════════════════════════════════════════════════════════════════════
bold "── 14. Data Mapper Compatibility ──"
# ═════════════════════════════════════════════════════════════════════════════
# Verify the API returns the exact field names the WP data mapper expects

yellow "  Checking meetings list fields match data mapper inputs..."
ML=$(aget "/meetings?limit=1" | jq '.data[0]')
echo "$ML" | jq -e 'has("meeting_date")' > /dev/null 2>&1 && pass "List has meeting_date (mapper → date)" || fail "List missing meeting_date"
echo "$ML" | jq -e 'has("meeting_time")' > /dev/null 2>&1 && pass "List has meeting_time (mapper → time)" || fail "List missing meeting_time"
echo "$ML" | jq -e '.council | has("id")' > /dev/null 2>&1 && pass "List has council.id (mapper → council_id)" || fail "List missing council.id"
echo "$ML" | jq -e '.council | has("name")' > /dev/null 2>&1 && pass "List has council.name (mapper → council_name)" || fail "List missing council.name"

yellow "  Checking meeting detail fields match data mapper inputs..."
MD=$(aget "/meetings/MTG-001" | jq '.data')
echo "$MD" | jq -e 'has("zoom_link")' > /dev/null 2>&1 && pass "Detail has zoom_link (mapper → zoom_url)" || fail "Detail missing zoom_link"
echo "$MD" | jq -e 'has("detail_url")' > /dev/null 2>&1 && pass "Detail has detail_url (mapper → notice_url)" || fail "Detail missing detail_url"
echo "$MD" | jq -e 'has("description")' > /dev/null 2>&1 && pass "Detail has description (mapper → agenda_text)" || fail "Detail missing description"
echo "$MD" | jq -e 'has("summary_text")' > /dev/null 2>&1 && pass "Detail has summary_text (mapper checks)" || fail "Detail missing summary_text"

yellow "  Checking councils fields match data mapper inputs..."
CL=$(aget "/councils" | jq '.data[0]')
echo "$CL" | jq -e 'has("upcoming_meeting_count")' > /dev/null 2>&1 && pass "Council has upcoming_meeting_count (mapper → meeting_count)" || fail "Council missing upcoming_meeting_count"
echo ""

# ═════════════════════════════════════════════════════════════════════════════
# Cleanup test data
# ═════════════════════════════════════════════════════════════════════════════

bold "── Cleanup ──"
docker exec appwebsite-db-1 mysql -u root -p"$ROOTPW" u325862315_access100 -e \
    "DELETE FROM subscriptions WHERE user_id IN (SELECT id FROM users WHERE email LIKE 'test-%@example.com');
     DELETE FROM users WHERE email LIKE 'test-%@example.com';" 2>/dev/null
green "  Cleaned up test subscriptions and users."
echo ""

# ═════════════════════════════════════════════════════════════════════════════
# Summary
# ═════════════════════════════════════════════════════════════════════════════

echo ""
bold "═══════════════════════════════════════"
bold "  Results: $PASS passed, $FAIL failed"
bold "═══════════════════════════════════════"

if [[ $FAIL -gt 0 ]]; then
    echo ""
    red "Failures:"
    for err in "${ERRORS[@]}"; do
        red "  - $err"
    done
    echo ""
    exit 1
else
    echo ""
    green "All tests passed!"
    exit 0
fi
