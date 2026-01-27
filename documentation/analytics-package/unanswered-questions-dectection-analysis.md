# Unanswered Questions Detection Analysis

**Audience**: Advanced users, developers, and support
**Purpose**: Explains how unanswered questions are detected and why results may vary
**Not required reading for normal use**

## How Unanswered Questions Are Currently Determined

The Insights Report uses a **pattern-matching approach** to identify unanswered questions. Here's the exact logic:

### Detection Criteria

A question is considered "unanswered" **ONLY** if one of these two scenarios occurs:

#### Scenario 1: Chatbot Fallback Response
- The chatbot responds with a message that matches specific fallback patterns
- The system then looks back in the same session to find the most recent human question before that fallback response

#### Scenario 2: Human Clarification/Confusion Message
- A human message (Visitor/User) contains clarification/confusion patterns
- The system looks back to find the original question that led to this confusion

### Fallback Patterns Used

The system checks for these patterns in chatbot responses or human messages:

**Basic Fallback Patterns:**
- "I'm not following"
- "Could you ask that"
- "That's unclear"
- "Didn't quite catch"
- "Could you try rephras"
- "Could you rephrase"
- "Try phrasing"
- "Please clarify"

**Failure and Apology Patterns:**
- "I don't know"
- "I am not sure" / "I'm not sure"
- "I can't help with that" / "I cannot help with that"
- "I don't have enough information"
- "I don't have that information"
- "I don't have access"
- "I don't have the ability"
- "I don't have details"
- "I'm unable to"
- "I can't answer" / "I cannot answer"

**Deflection Patterns:**
- "As an AI"
- "I am an AI"
- "I am a language model"
- "I don't have personal opinions"
- "I don't have real-time"
- "I don't have browsing"
- "I don't have context"

**Clarification Requests:**
- "Can you provide more details"
- "Can you give more information"
- "Can you be more specific"
- "What do you mean"
- "Can you elaborate"
- "I need more context"
- "Not enough context"

**External Help Patterns:**
- "You may want to contact"
- "You should contact"
- "Check with customer support"
- "Reach out to support"
- "Consult a professional"
- "Visit the official website"

**Safety/Policy Patterns:**
- "I can't assist with that request"
- "I can't help with this request"
- "I'm unable to comply"
- "This request is not allowed"
- "I can't provide that"

**Conversation Breakdown Patterns:**
- "Let's change the topic"
- "I might be misunderstanding"
- "That doesn't seem related"

### SQL Query Logic

The query:
1. Finds messages (`c`) in the reporting period that match fallback patterns (either chatbot responses OR human clarification messages)
2. Joins to find the most recent human question (`q`) in the same session that occurred BEFORE the fallback/clarification message
3. Groups identical questions together
4. Counts how many times each question triggered a fallback
5. Returns the top questions by frequency (default limit: 5)

### Key Limitations

**Why You Might Only See 1 Unanswered Question:**

1. **Pattern Matching is Strict**: If the chatbot responds with ANY text that doesn't match these specific patterns, even if the answer is wrong, incomplete, or unhelpful, it won't be counted as "unanswered."

2. **Only Explicit Fallbacks Count**: The system only detects questions when the chatbot explicitly signals it can't answer (via fallback patterns). If the chatbot gives a generic, off-topic, or incorrect answer without using these phrases, it won't be detected.

3. **Case Sensitivity**: Patterns use `LIKE` with wildcards, but they're case-insensitive due to the `%` wildcards. However, exact phrase matching is required.

4. **Session-Based**: Questions are only matched to fallbacks within the same session. If a fallback occurs in a different session, it won't be linked.

5. **Most Recent Question Only**: The system only looks for the MOST RECENT human question before a fallback. If there were multiple questions in a row, only the last one is captured.

6. **Grouping by Exact Text**: Questions are grouped by exact text match. Slight variations ("What is pricing?" vs "What's the pricing?") are counted separately.

### Example Scenarios

**Scenario A - Would Be Detected:**
```
User: "Do you offer volume discounts?"
Chatbot: "I'm not sure about that. Could you contact support?"
```
✅ **Detected** - Contains "I'm not sure" pattern

**Scenario B - Would NOT Be Detected:**
```
User: "What are your pricing tiers?"
Chatbot: "We offer various pricing options. Please visit our website for details."
```
❌ **Not Detected** - No fallback pattern, even though it's not a helpful answer

**Scenario C - Would NOT Be Detected:**
```
User: "How do I integrate the API?"
Chatbot: "Here's some general information about APIs..." [generic/unhelpful response]
```
❌ **Not Detected** - Chatbot gave a response, just not a useful one

**Scenario D - Would Be Detected:**
```
User: "Can you help me with X?"
Chatbot: "I don't have enough information to answer that."
```
✅ **Detected** - Contains "I don't have enough information" pattern

### Recommendations

1. **Review Actual Chatbot Responses**: Check the conversation logs to see what the chatbot actually responded with. If responses don't match fallback patterns, they won't be detected.

2. **Expand Fallback Patterns**: Consider adding more patterns if your chatbot uses different phrasing for unclear questions.

3. **Add Debugging**: Enable query logging to see what the SQL query is actually finding.

4. **Consider Alternative Metrics**: Instead of just pattern matching, consider:
   - Response quality scoring
   - User satisfaction indicators
   - Follow-up question patterns
   - Conversation length/complexity metrics

### Code Location

The detection logic is in:
- **Function**: `kognetiks_insights_get_top_unanswered_questions()` 
- **File**: `includes/insights/automated-emails.php` (lines 325-441)
- **Patterns**: `includes/insights/languages/en_US.php` (lines 101-189)

### Filter Hook Available

Results can be modified using the WordPress filter:
```php
apply_filters( 'kognetiks_insights_top_unanswered_questions', $out, $start_ts, $end_ts, $limit )
```

This allows custom filtering or modification of the results before they're included in the report.
