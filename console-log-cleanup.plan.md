# Console Log Cleanup Plan

## Critical Issues Found

### 1. Negative Page Load Time (CRITICAL BUG)

**Problem**: Page load time calculation showing negative values

- `Page Load Time: -1761091884865ms` (negative!)
- Location: `wp-content/themes/flexpress/includes/performance-optimization.php:223`

**Root Cause**:

- `timing.loadEventEnd` is 0 when the event hasn't fired yet
- Calculation: `loadEventEnd - navigationStart` results in negative value
- This happens because the script runs before `loadEventEnd` is populated

**Solution**:

- Add check to ensure `loadEventEnd > 0` before calculating
- Only log when timing data is valid
- Wrap console logs in WP_DEBUG conditional

### 2. Debug Console Logs (PRODUCTION ISSUE)

**Problem**: Performance monitoring logs appearing in production console

- `Page Load Time: Xms` (line 223)
- `DOM Ready Time: Xms` (line 224)
- `ServiceWorker registration successful` (line 335)
- `ServiceWorker registration failed` (line 338)

**Solution**:

- Wrap all console.log statements in WP_DEBUG conditionals
- Keep error logging for ServiceWorker failures
- Remove or conditionalize success messages

### 3. MutationObserver TypeError (EXTERNAL - LOW PRIORITY)

**Problem**: Third-party script error

- `TypeError: Failed to execute 'observe' on 'MutationObserver': parameter 1 is not of type 'Node'`
- Source: `index.ts-aa24d275.js` (external script, likely Cloudflare Turnstile)

**Analysis**:

- This is from an external/third-party service
- Already has error suppression in `admin-affiliate-spa.js:621-628`
- Cannot fix directly as it's not our code

**Solution**:

- Add global error handler to suppress this specific third-party error
- Prevent it from cluttering console in production

### 4. jQuery Migrate Warning (MODERATE)

**Problem**: jQuery Migrate notification in console

- `JQMIGRATE: Migrate is installed, version 3.4.1`

**Solution**:

- Evaluate if jQuery Migrate is still needed
- If needed, disable warnings in production
- Consider updating jQuery-dependent code to remove need for Migrate

## Implementation Steps

1. **Fix negative page load time calculation**

   - Add validation checks for timing values
   - Only calculate when `loadEventEnd > 0`

2. **Wrap performance monitoring logs in WP_DEBUG**

   - Conditional console.log for page load time
   - Conditional console.log for DOM ready time
   - Keep server-side logging for slow pages

3. **Wrap ServiceWorker logs in WP_DEBUG**

   - Conditional success message
   - Keep error logging (important for debugging)

4. **Add global error suppression for third-party errors**

   - Suppress MutationObserver errors from external scripts
   - Only in production (not in debug mode)

5. **Handle jQuery Migrate warnings**
   - Disable warnings in production via jQuery Migrate configuration
   - Or remove jQuery Migrate if not needed

## Files to Modify

1. `wp-content/themes/flexpress/includes/performance-optimization.php`

   - Lines 215-238: Performance monitoring
   - Lines 327-344: ServiceWorker registration

2. `wp-content/themes/flexpress/functions.php` (or header.php)
   - Add global error handler for third-party errors
   - Add jQuery Migrate warning suppression

## Expected Outcome

**Before (Production Console)**:

```
TypeError: Failed to execute 'observe' on 'MutationObserver'...
JQMIGRATE: Migrate is installed, version 3.4.1
Page Load Time: -1761091884865ms
DOM Ready Time: 2553ms
ServiceWorker registration successful
```

**After (Production Console)**:

```
(Clean - no debug logs)
(Only actual errors from our code)
```

**After (Development Console with WP_DEBUG=true)**:

```
Page Load Time: 1234ms
DOM Ready Time: 567ms
ServiceWorker registration successful
```

## Testing Checklist

- [ ] Verify page load time is positive and accurate
- [ ] Verify console is clean in production (WP_DEBUG=false)
- [ ] Verify debug logs appear in development (WP_DEBUG=true)
- [ ] Verify ServiceWorker errors still log (important for debugging)
- [ ] Verify third-party errors are suppressed
- [ ] Test on multiple pages (home, episodes, casting, etc.)

## Production Deployment Notes

1. Ensure `WP_DEBUG` is set to `false` in production `wp-config.php`
2. Clear browser cache after deployment
3. Test console on multiple browsers
4. Monitor for any legitimate errors that might be suppressed
