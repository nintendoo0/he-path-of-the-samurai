# Support Request for AstronomyAPI

## Email Template

**To:** support@astronomyapi.com  
**Subject:** AWS IAM Policy Blocking API Access - Account ID: 9ec70b76-acfc-4f96-bed0-81509abf8d84

---

Hello AstronomyAPI Support Team,

I am experiencing issues accessing the AstronomyAPI endpoints with my registered account. All API requests are returning HTTP 403 errors with AWS IAM policy denial messages.

**Account Details:**
- Application ID: `9ec70b76-acfc-4f96-bed0-81509abf8d84`
- Registration Date: December 2025
- Account verified on dashboard: Yes

**Error Details:**
```json
{
  "Message": "User is not authorized to access this resource with an explicit deny in an identity-based policy"
}
```

**Tested Endpoints:**
- `/api/v2/bodies` - HTTP 403
- `/api/v2/bodies/events/sun` - HTTP 403
- `/api/v2/bodies/events/moon` - HTTP 403

**Authentication Method:**
Using Basic Authentication as per documentation:
```
Authorization: Basic base64(applicationId:applicationSecret)
```

The credentials are verified as valid on the dashboard ("Credentials valid" message shown), but all API endpoints return 403 errors indicating AWS IAM policy denial.

**Questions:**
1. Is there an account activation step I'm missing?
2. Has the free tier been migrated to a different authentication method?
3. Are there additional AWS IAM permissions required for the account?
4. Do I need to upgrade to a paid plan to access the API?

**My Use Case:**
I'm building an educational space monitoring application that displays astronomical events (sunrise, sunset, moon phases) for students. The project is open-source and non-commercial.

**System Information:**
- Using latest API documentation: https://docs.astronomyapi.com/
- Testing from: Docker PHP 8.2 environment
- Request format validated with multiple methods (curl, PHP HTTP client)

I would greatly appreciate your assistance in resolving this issue. Thank you for maintaining this valuable API service!

Best regards,
[Your Name]

---

## Alternative Actions

If support doesn't respond within 48 hours:

1. **Check Dashboard Settings:**
   - https://astronomyapi.com/dashboard
   - Look for "Account Status" or "Subscription" section
   - Check if email verification is pending
   - Look for IAM policy settings

2. **Try Creating New Application:**
   - Create a new application in the dashboard
   - Generate new Application ID and Secret
   - Test with new credentials

3. **Community Support:**
   - Check GitHub issues: https://github.com/AstronomyAPI
   - Join their Discord/Slack if available
   - Post on Stack Overflow with tag [astronomyapi]

4. **Temporary Workaround:**
   - Use Open-Meteo API (currently implemented)
   - Use Sunrise-Sunset.org API
   - Use NASA APIs for astronomical data

## Testing New Credentials

If you get new credentials from the dashboard, update in `.env`:

```env
ASTRO_APP_ID=your_new_app_id_here
ASTRO_APP_SECRET=your_new_secret_here
```

Then restart the PHP container:
```bash
docker-compose restart php
```

Test with:
```bash
curl "http://localhost:8080/api/astro/events?force_astronomy=true"
```
