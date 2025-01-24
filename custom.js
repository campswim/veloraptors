// Add the RSVP link dynamically to the calendar.
const currentCalendar = document.querySelector('.simcal-current');
if (currentCalendar) {
  // Get today's date.
  const today = new Intl.DateTimeFormat('en-US', {
    timeZone: 'America/Los_Angeles',
    day: 'numeric',
  }).format(new Date());

  // Get the value of the 'data-calendar-current' attribute and convert it into month and year.
  const dataEventsFirst = currentCalendar.getAttribute('data-calendar-current');
  const date = new Date(parseInt(dataEventsFirst * 1000));
  let month = date.getMonth() + 1; // Add 1 to get a 1-based month
  const year = date.getFullYear();
  const eventDetails = document.querySelectorAll('.simcal-event-details');
  let eventTitle = '',
    day = '';

  // Add a mouseover listener to all events.
  document.querySelectorAll('.simcal-events').forEach((element) => {
    element.addEventListener('click', (event) => {
      eventTitle = event?.target?.innerText;

      // Get the event's day.
      day =
        event?.target?.parentElement?.parentElement?.previousElementSibling
          ?.innerText;

      if (eventTitle && day && Number(today) <= Number(day)) {
        if (day.length === 1) day = `0${day}`;
        if (JSON.stringify(month).length === 1) month = `0${month}`;
        eventTitle = eventTitle.split(' ').join('-').toLowerCase();
        const dateFormatted = `${year}-${month}-${day}`;
        const regex = /(^|\s)simcal-event-start-date(\s|$)/;

        if (eventDetails) {
          eventDetails.forEach((event) => {
            let detailsDate = '';

            // Get the event detail's date for squaring with the chosen date.
            event?.childNodes.forEach((node) => {
              const childNodes = node?.childNodes;

              if (childNodes) {
                childNodes.forEach((childNode) => {
                  const classes = childNode?.className;

                  if (classes && regex.test(classes)) {
                    const attributes = childNode?.attributes;

                    if (attributes) {
                      Array.from(attributes).forEach((attribute) => {
                        if (attribute.name === 'content')
                          detailsDate = attribute.textContent.split('T')[0];
                      });
                    }
                  }
                });
              }
            });

            if (detailsDate === dateFormatted) {
              const descriptionElement = event;

              if (
                descriptionElement &&
                !descriptionElement.querySelector('.rsvp-link')
              ) {
                const eventPath = `/rsvp/?event=${eventTitle}&date=${year}-${month}-${day}`;
                const rsvpUrl = window.location.origin + eventPath;
                const htmlContent = `<br /><a href="${rsvpUrl}" target="_blank" rel="noopener noreferrer">RSVP</a> for this event.`;
                const newChildNode = document.createElement('p');
                newChildNode.className = 'rsvp-link';
                newChildNode.innerHTML = htmlContent;
                descriptionElement.appendChild(newChildNode);
              }
            }
          });
        }
      }
    });
  });
}

// Open tabs dynamically and disable the default scrolling to the hash.
jQuery(document).ready(function ($) {
  const hash = window.location.hash;

  if (hash) {
    // Remove the hash immediately to prevent any default scrolling behavior.
    history.pushState(null, null, window.location.href.split('#')[0]);

    // Prevent any scrolling due to hash by monitoring the page's DOM and applying a fix.
    const observer = new MutationObserver((mutations) => {
      mutations.forEach((mutation) => {
        if (mutation.type === 'childList') {
          // Ensure no automatic scroll.
          $('html, body').scrollTop(0);
        }
      });
    });

    // Configure the observer to watch for changes in the body (adding/removing elements).
    observer.observe(document.documentElement, {
      childList: true,
      subtree: true,
      attributes: true,
    });

    // After page load and mutation is settled, open the tab.
    setTimeout(function () {
      const targetTabButton = $(hash); // Get the tab button using the hash.
      if (targetTabButton.length) {
        // Trigger the tab click event
        targetTabButton.trigger('click');
      }

      // Stop observing once the tab has been triggered.
      observer.disconnect();
    }, 100); // Delay to wait for Elementor to process the page load.
  }
});

// Change the color of the Simple Calendar nav arrows so they'll show.
const navArrows = document.querySelectorAll('.simcal-nav-button');
if (navArrows) navArrows.forEach((arrow) => (arrow.style.color = 'black'));

/* Increase the max-width of the container when the page is full-roster. */
// Ensure the siteData variable exists
if (typeof siteData !== 'undefined' && siteData.pageUri === 'full-roster') {
  const style = document.createElement('style');
  style.textContent = `
    @media all and (min-width: 1024px) {
      .elementor-section.elementor-section-boxed > .elementor-container {
        max-width: 60% !important;
      }
      .elementor-section.elementor-section-boxed > .elementor-container > .elementor-column > .elementor-widget-wrap > .elementor-element > .elementor-widget-container > .gp-element-post-title {
        margin-top: 4rem;
      }
    }
  `;
  document.head.appendChild(style);
}

// Hide the "Most Popular" tag on the find-your-plan page.
const mostPopularTag = document.querySelector('.gp-level-badge');
if (mostPopularTag) mostPopularTag.style.display = 'none';
