# Cart / orders fixes — note on CF4-72

Corrections applied under this workstream cover **client cart synchronization**, **checkout**, **payment method selection**, **ready-to-pickup notifications and pickup countdown**, **transactional email base URLs**, **Google OAuth session reliability**, and related **Spanish copy**.

They **do not implement nor resolve [CF4-72]** (product variant editing). Do **not** use commit messages or PR text such as `fixes CF4-72`, `closes #… CF4-72`, or mark CF4-72 as Done solely because of these changes.

If traceability is needed, create **separate Jira bugs** for cart, checkout, notifications, email URLs, pickup TTL, OAuth, and copy.

The Zephyr / manual test mismatch (e.g. references to CA5/CA6 vs four acceptance criteria on CF4-72) should be corrected in **Jira/Zephyr**, not via this codebase.
