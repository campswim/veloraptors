// Return today's date, e.g., 18.
const getTodaysDate = () => {
  return new Intl.DateTimeFormat('en-US', {
    timeZone: 'America/Los_Angeles',
    month: 'numeric',
    day: 'numeric',
  }).format(new Date());
};

// Add RSVP links to all events in the Simple Calendar.
const addRSVPLink = () => {
  if (!rsvpEnabled) return;  // If RSVP is disabled, exit the function.
  
  // Add the RSVP link dynamically to the calendar.
  const currentCalendar = document.querySelector('.simcal-current');
  
  if (currentCalendar) {
    // Get today's date.
    const today = getTodaysDate();
    const [todaysMonth, todaysDay] = today?.split('/');

    // Get the value of the 'data-calendar-current' attribute and convert it into month and year.
    const dataEventsFirst = currentCalendar.getAttribute('data-calendar-current');
    const date = new Date(parseInt(dataEventsFirst * 1000));
    let month = date.getMonth() + 1; // Add 1 to get a 1-based month
    const year = date.getFullYear();
    const eventDetails = document.querySelectorAll('.simcal-event-details');
    let eventTitle = '', day = '';

    // Add a click listener to all events.
    document.querySelectorAll('.simcal-events').forEach(element => {
      element.addEventListener('click', event => {
        eventTitle = event?.target?.innerText;

        // Get the event's day.
        day = event?.target?.parentElement?.parentElement?.previousElementSibling?.innerText;

        if (eventTitle && day && (Number(todaysDay) <= Number(day) || Number(todaysMonth) < month)) {
          if (day.length === 1) day = `0${day}`;
          if (JSON.stringify(month).length === 1) month = `0${month}`;
          eventTitle = eventTitle.split(' ').join('-').toLowerCase();

          const dateFormatted = `${year}-${month}-${day}`;
          const regex = /(^|\s)simcal-event-start-date(\s|$)/;

          if (eventDetails) {
            eventDetails.forEach(event => {
              let detailsDate = '';

              // Get the event detail's date for squaring with the chosen date.
              event?.childNodes.forEach(node => {
                const childNodes = node?.childNodes;

                if (childNodes) {
                  childNodes.forEach(childNode => {
                    const classes = childNode?.className;

                    if (classes && regex.test(classes)) {
                      const attributes = childNode?.attributes;

                      if (attributes) {
                        Array.from(attributes).forEach(attribute => {
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
                  const htmlContent = `<br /><a href="${rsvpUrl}">RSVP</a> for this event.`;
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
};

// Correct the disabled attribute of the Simple Calendar based on the current months and start and end months. (The plugin doesn't do this accurately.)
const correctCalNavButtons = () => {
  const simpleCalendar = document.querySelector('.simcal-calendar');
  const currentCalendar = document.querySelector('.simcal-current');
  const navArrows = document.querySelectorAll('.simcal-nav-button');  
  const currentCalendarTimestamp = currentCalendar.getAttribute('data-calendar-current');
  const currentCalendarDate = new Date(parseInt(currentCalendarTimestamp * 1000));
  const currentCalendarMonth = currentCalendarDate.getMonth() + 1; // Add 1 to get a 1-based month.
  const currentCalendarYear = currentCalendarDate.getFullYear();
  let startMonth, startYear, endMonth, endYear, prevArrow, nextArrow;

  if (simpleCalendar) {
    let startDate = simpleCalendar.getAttribute('data-calendar-start');
    let endDate = simpleCalendar.getAttribute('data-calendar-end');

    if (startDate && endDate) {
      startDate = new Date(parseInt(startDate * 1000));
      endDate = new Date(parseInt(endDate * 1000));
      startMonth = startDate.getMonth() + 1; // Add 1 to get a 1-based month
      startYear = startDate.getFullYear();
      endMonth = endDate.getMonth() + 1;
      endYear = endDate.getFullYear();
    }
  }

  if (navArrows) {
    prevArrow = navArrows[0];
    nextArrow = navArrows[1];
  }


  if (startMonth && startYear && endMonth && endYear && currentCalendarMonth && currentCalendarYear && prevArrow && nextArrow) {
    if (currentCalendarMonth === startMonth) {
      prevArrow.disabled = true;
      nextArrow.disabled = false;
    } else if (currentCalendarMonth === endMonth) {
      prevArrow.disabled = false;
      nextArrow.disabled = true;
    } else {
      prevArrow.disabled = false;
      nextArrow.disabled = false;
    }
  }
}

// Call add RSVPLink every time a user navigates between months to ensure that the right calendar is being referenced.
const observeCurrentCalendarChanges = () => {
  const targetNode = document.querySelector('.simcal-current');

  if (!targetNode) return;

  const observer = new MutationObserver(mutations => {
    mutations.forEach(mutation => {
      if (mutation.attributeName === 'data-calendar-current') {
        addRSVPLink();
        correctCalNavButtons();
      }
    });
  });

  observer.observe(targetNode, { attributes: true });
};

// Increase the max-width of the container when the page is full-roster.
const increaseMaxWidth = () => {
  // Ensure the siteData variable exists.
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
}

// Open tabs dynamically and disable the default scrolling to the hash.
const tabRedirectAndScrollSupppression = () => {
  jQuery(document).ready(function ($) {
    const hash = window.location.hash;
  
    if (hash) {
      // Remove the hash immediately to prevent any default scrolling behavior
      history.replaceState(null, null, window.location.href.split('#')[0]);
  
      // Temporarily disable scrolling for all browsers
      $('html, body').css({ overflow: 'hidden', 'scroll-behavior': 'auto' });
  
      // Force scroll to top (especially important for Safari)
      window.scrollTo(0, 0);
  
      // MutationObserver to monitor DOM changes (in case other elements try to scroll)
      const observer = new MutationObserver(() => {
        window.scrollTo(0, 0); // Ensure scroll is always at top
      });
  
      observer.observe(document.body, { childList: true, subtree: true });
  
      // After some time, allow scroll and trigger the tab
      setTimeout(() => {
        // Allow scrolling again
        $('html, body').css({ overflow: '', 'scroll-behavior': '' });
  
        // Open the tab using the hash
        const targetTabButton = $(hash);
        if (targetTabButton.length) {
          targetTabButton.trigger('click');
        }
  
        // Stop observing after the action is completed
        observer.disconnect();
      }, 500); // Longer delay for Safari's rendering and scroll behavior
  
      // Extra safeguard: attempt to block Safari's scroll restoration
      setTimeout(() => {
        window.scrollTo(0, 0);
      }, 0); // A small delay to ensure it's the last thing Safari processes
    }
  });
}

// Hide the "Most Popular" tag on the find-your-plan page.
const hidePopularTag = () => {
  const mostPopularTag = document.querySelectorAll('.gp-level-badge');
  
  if (mostPopularTag) {
    Object.values(mostPopularTag).forEach(tag => {
      tag.style.display = 'none';
    });
  }
}

// Disable the "Select" button for the Board Member level. (This occurs on two differenet pages.)
const disableBoardMemberSelect = () => {
  const pmproSelectButtonOne = document.querySelector('.pmpro_btn');
  const pmproSelectButtonTwo = document.querySelector('.gp-level')?.lastChild;
  
  if (pmproSelectButtonOne) pmproSelectButtonOne.href = '';
  if (pmproSelectButtonTwo) pmproSelectButtonTwo.innerHTML = '';
}

const truncateFaqs = () => {
  const uri = window.location.pathname;
  if (uri === '/faqs/') {
    const textToTruncate = document.querySelectorAll('.gp-element-post-excerpt');
    if (textToTruncate && textToTruncate.length) {
      textToTruncate.forEach(text => {
        const textContent = text.textContent;
        let truncatedText = '';
        if (textContent && textContent.includes('?')) truncatedText = textContent.split('?')[0] + '?';
        else truncatedText = textContent.slice(0, 200) + '...';
        text.innerText = truncatedText;
      });
    }
  }
}

// Call JS functions only after the DOM has been loaded.
document.addEventListener('DOMContentLoaded', () => {
  addRSVPLink();
  observeCurrentCalendarChanges();
  increaseMaxWidth();
  tabRedirectAndScrollSupppression();
  hidePopularTag();
  disableBoardMemberSelect();
  truncateFaqs();
});
