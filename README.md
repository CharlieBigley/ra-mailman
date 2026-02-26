# Ramblers Mailman Component

The Ramblers Mailman component aims to help Areas and Groups manage their email communications with members.

## Objectives
- Comply fully with the [General Data Protection Regulations](https://https://docs.stokeandnewcastleramblers.org.uk/index.php?option=com_content&view=article&id=437:mm-10-appendix-gpdr&catid=34:mailman&Itemid=1404)
- Implement quickly, without waiting any longer for the centrally provided functionality that was promised for 2019
- Use and administer simply
- Increase engagement with Group membership
- Allow emails to persons or organisations who are not members
- Keep a full audit trail of updates to subscriptions
- Prepare to implement Single Sign On, and therefore share the corporate system already paid for
- Configure for a single Group or a whole Area

## Key Features
1. Allow a member to opt into the group's "Members newsletter" but stay opted out of "marketing" emails from Central Office.
2. A number of different mailing lists can be defined for different sub-sets of members.
    - A special list is established and used by the system for "Official" communications such as notification of AGMs. Members cannot opt out of this, but should their membership lapse, they will be unsubscribed.
    - You will also need a second, similar list called "Members Newsletter". This will initially include all of the membership, but some may choose to opt out of it, and others may choose to join if they have an email address they don't want to divulge to Central Office. Furthermore, members from neighbouring groups may wish to be kept informed of social events, coach trips etc.
    - Some of these lists are "Closed", and subscriptions are controlled by officers of their Group (examples are a list of footpath volunteers, or the list of committee members)
    - Others are "Open", and members can subscribe or cancel at will (an example could be those interested in a weekend away).
3. There are five methods that a User record can be created:
    - Manually, using the standard Joomla functionality.
    - Uploading a simple csv file comprising group code, name and email.
    - Importing a file exported from MailChimp.
    - Importing a file from the corporate Social Secretaries reporting system (as at Feb 2022 this is the Insight Hub).
    - Allowing members of the public to self-register. (If you wish to allow this functionality)
    - If either of the methods 3c or 3d are used, the User will additionally be subscribed onto the mailing list "Members newsletter".
4. Any member can be on multiple lists.
5. If a User record is created by method 3a it is the responsibility of the group webmaster to give the password to the user. For 3e, after this process the user will know their password, and will subsequently be able to log in to the site, and may choose to subscribe to any Open lists. Furthermore, any suitably authorised committee member may manually subscribe them onto Closed lists.
6. If a member has been registered by methods 3b, 3c or 3d, they will not initially know their password, but using the standard Joomla functionality will be able to request a password reset.
7. If method 3e is permitted by the webmaster, anyone can register to use the system, and may then subscribe themselves to any Open list.
8. An interface is provided that reads a download from the corporate system, which must be run manually prior to sending out any emails. This automatically subscribes everyone onto the mailing list "Official Notices", unless they have already been registered on it. I recommend they are also added to the list "Members Newsletter" so they can be informed of your local activities. This is permitted by the GPDR since they will be able to opt out. However, if you have a standard "welcome" email or letter you send out, it would be a good idea to mention in that they should expect occasional emails from the Group.
9. When the corporate interface is processed, if any user records were created from a previous data-load but are not present on the most recent interface file, then it is assumed that the member in question has let their membership lapse, or that they have opted out of communications. All their subscriptions would be cancelled. *(As of October 2025 this functionality is not fully implemented)*
10. The method of registration is recorded for each User of the system.
11. It is possible for a user to be opted out of the national "marketing" but still opt in to emails from their local group (either by self registering, or by making a specific request to an officer of the group). If they are registered in this way, their subscription will not be cancelled by the corporate feed.
12. An additional interface is planned that will process a list of lapsed members and cancel all their subscriptions, should Central Office be capable of providing such a list.
13. If a User was registered by methods 3a, 3b or 3c above, their subscriptions will be managed by the interface or officers of the Group, but they will have on-line access to all historical mailshots.
14. Regardless of the method used to enrol them, each email will contain a link to facilitate cancellation of the subscription.
15. If the optional RA Events component is installed, then the recipient of a Mailshot can be allowed to book a place on an Event without first having to log on to the website.

This list of features is expected to change over time in response to user feedback.

This page is an extract from the User Manual, located at [https://docs.stokeandnewcastleramblers.org.uk/mail-manager](https://docs.stokeandnewcastleramblers.org.uk/mail-manager)
