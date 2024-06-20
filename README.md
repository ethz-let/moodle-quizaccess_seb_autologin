# moodle-quizaccess_seb_autologin
![](https://github.com/ethz-let/moodle-quizaccess_seb_autologin/actions/workflows/moodle-plugin-ci.yml/badge.svg)

# SEB Auto-Login Access rule.

* This accessrule plugin (/mod/quiz/accessrule/seb_autologin) allows users to auto login to moodle when launching a quiz that is using SEB accessrule (with upload client config, or manually created config options).

* The plugin also offers access to current logged in user info. This info is used by SEB Client.

# Restrictions
* Autologin is NOT applicable to moodle admins. For obvious security reasons, admins will still need to re-login.
* Token expires in 5 mins (moodle default is 60 seconds), in order to give the end user enough time to launch SEB.
* Token is restircted to the current user IP address.
* Webservices must be enabled. The only rationale here is to assume that site policy allows external services.
* Moodle must be in https.
* not guest, not suspended accounts etc
* It validates (and terminates) concurrent sessions based on moodle max allowed concurrent logins settings.

# Required moodle version:
 Min required version 4.2

# Disclaimer:
Every effort has been put into this plugin to work around SEB core plugin limited access scope and strucutre, hence, and due to the limited felxibility in SEB plugin code, the plugin is not written in the most efficient way. Please keep in your mind that the plugin might have an impact or a negative effect on your setup.
