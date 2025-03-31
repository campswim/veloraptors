# Velo Raptors Cycling Club

A WordPress site for the Bay Area's VeloRaptors Cycling Club that includes user authentication and authorization; online membership application and member administration; administrative control of access to content based on membership level and WordPress user role; guest and members-only calendars, integrated with Google calendars; event regsitration and member-accessible registration management and listing; responsive design for all viewports; viewable and editable member profiles and group profiles; members' activity stream, public and private messaging, frieding, and configurable email notifications; automated emails with configurable email templates; configurable styling via plugins' settings, templates, Elementor, and the WordPress block editor.

## Theme

Zine by GhostPool.

## Child Theme

Magzine Child by Nathan Cox

## Features by Plugin

1. Paid Memberships Pro (Free Tier)

    - Login/out, including password reset
    - Member registration and payment, witih terms-of-service requirement
    - Configuration of the checkout page
    - Management of membership applications, members, member profiles, membership orders, memberhsip expiration dates, and access to members-only content and features
    - Automated outgoing emails with event triggers (not configurable)
    - Editing of email templates, with dynamic variables, and enabling/disabling of automated emails

2. BuddyPress (Free Tier)

    - Editable and configurable member profile page with customizable avatar and banner image
    - Public and private posting, friending, and messaging
    - Friend and group search, with sorting and filtering
    - Configurable email notifications
    - Automated outgoing emails with configurable event triggers
    - Editing of email templates in both HTML and plain text, with dynamica variables, and enabling/disabling of automated emails
    - Extensive (if confusing and inexhaustive) styling controls

3. Simple Calendar (Free Tier)

    - Google calendar integration (with Google API key)
    - Configurable, with minimal styling options

4. WPForms Lite

    - Configurable, editable forms with configurable spam protection
    - Email forwarding of the contact-us message

5. Elementor + GhostPool Zine Theme

    - Items widget: configurable taxonomy and meta queries
    - Items widget: configurable GhostPool template loop
    - Image carousel: quick-and-easy solution for creating image carousels, used on this site's photo-gallery page

6. GhostPool Zine Theme

    - Template parts that can be plugged in and out of any page built with the default template
    - Stylable at both the macro and micro level
    - Configurable settings

## Custom Features and Fixes

1. Custom RSVP Plugin

    - Event registration via RSVP link, appended to each event's description on the Simple Calendar plugin's calendars
    - Dynamically created menu links for the RSVP pages
    - Programmatic purging of expired RSVP pages, links, and database records
    - Attendee list viewable for all members, with hyperlinked members' names that redirect to their owners' profiles
    - Registration cancellation by member or administrator
    - Language reflecting the visitor's RSVP status--whether guest's or member's--when viewing an event's RSVP page
    - RSVPs and RSVP Pages dashboards
    - Button on the RSVPs dashboard to disable or enable the RSVP feature

2. Fixes

  a. Styling

    - WPForms Lite plugin's text-area placeholder's font made the placeholder invisible, requiring custom CSS.
    - Adding a membership level of "board member," but hiding it from being available for registration by site visitors required JavaScript and CSS.
    - With only one membership level available for the club, the "Most Popular" tag automatically rendered on the level's registration card required CSS to hide it.
    - Elementor's image carousel's navigation arrows had to be hidden via custom CSS in mobile, because of the fact that the carousel's height, upon wich the arrows' location is based, is fixed and determined by the photo in the colleciton with the greatest height value.
    - Because of the complexity of BuddyPress's style-settings UI, some paragraph and anchor tags' color had to be addressed with custom CSS.
    - Sometimes Elementor doesn't update the background image of a post's banner. To fix this, the Background Type of the inner-section block of the post-title template (under GhostPool Templates) has to be set to default, saved, and then set back to Featured Image and duly save again.
    - Elementor's items widget's date-range feature under the query dropdown doesn't work properly, so a workaround was developed using WordPress's categories to include or exclude old posts.
    - The word "password" on the login modal after a failed login attempt goes vertical, requiring custom CSS to behave properly.
    - PMPro's checkout page in mobile allowed several elements to overflow the viewport, requiring custom CSS to behave properly.
    - A section of both homepages on desktop feature a background image that stays fixed when the page is scrolled, but required custom CSS to allow for the same functionality on mobile.
    - 
      
  b. Functionality

    - Elementor's items widget's pagination, added by the GhostPool theme, would either disappear or fail to load the next set of posts, because of an improperly scoped variable in the items.php file; this error was reported, and the developer responded with a thank-you and the promise to fix the error in the theme's next update.
    -  Elementor's items wdiget, modified by the GhostPool theme, loads all posts on next-arrow click when there is more than one tax query present, because the query, stored as an attribute on the pagination's HTML element, was malformed. The fix, also in items.php, was to change `data-tax-query=' . $tax_query_json . '` to `data-tax-query="' . esc_attr( $tax_query_json ) . '"`, escaping the JSON properlyl. The error was fixed in the theme and reported to the developer, who againi replied with gratitude and a promise to include the fix in the theme's next update.
    - 

## Review of the Zine Theme

A powerful theme that generally works out of the box, Zine's WordPress setup is complicated, producing a steep learning curve, especially for less experienced WordPress developers and administrators, and its implementation is buggy.

- The login/out feature, while sometimes buggy--resulting in poor formatting in mobile, the rendering of two login forms after a failed login attempt, and the failure to login from time to time without first clearing cookie--it works as expected.
- Submitting tickets to the developer always resulted in a response, though those responses were typically restricted to an expression of gratitude and a promise to include a fix in the next update.
- The checkout page's layout allowed several sections to overflow the viewport in mobile.
- The Simple Calendar plugin's calendar's navigation arrows didn't render reliably and had to be modified.
