// Return today's date, e.g., 18.
if (typeof getTodaysDate === 'undefined') {
  var getTodaysDate = () => {
    return new Intl.DateTimeFormat('en-US', {
      timeZone: 'America/Los_Angeles',
      month: 'numeric',
      day: 'numeric',
    }).format(new Date());
  };
}

// Add RSVP links to all events in the Simple Calendar: viewport >= 467px.
if (typeof addRSVPLinkDesktop === 'undefined') {
  var addRSVPLinkDesktop = () => {
    if (!rsvpEnabled) return;  // If RSVP is disabled, exit the function.
    const monthMap = {
      January: '01',
      February: '02',
      March: '03',
      April: '04',
      May: '05',
      June: '06',
      July: '07',
      August: '08',
      September: '09',
      October: '10',
      November: '11',
      December: '12'
    };

    const observer = new MutationObserver(mutations => {
      mutations.forEach((mutation) => {
        if (mutation.attributeName === 'style') {
          const target = mutation.target;
          let eventTitle = '', eventDates = '';
          
          if (getComputedStyle(target).display === 'block'){           
            for (const [index, child] of Object.entries(target?.children)) {
              if (index === '0') eventTitle = child?.innerText;
              else if (index === '1') eventDates = child?.innerText;
            }

            if (eventTitle && eventDates) {
              const eventStartDate = eventDates.split(' - ')[0] ? eventDates.split(' - ')[0].trim() : '';
              const eventEndDate = eventDates.split(' - ')[1] ? eventDates.split(' - ')[1].trim() : '';
              let startDay = '', startMonth = '', startYear = '';
              let endDay = '', endMonth = '', endYear = '';

              if (eventStartDate) {
                startDay = eventStartDate.split(',')[0] ? eventStartDate.split(',')[0].trim() : '';
                startDay = startDay?.split(' ')[1] ? startDay.split(' ')[1].trim() : '';
                startDay = startDay.length === 1 ? '0' + startDay : startDay;
                startMonth = eventStartDate.split(',')[0] ? eventStartDate.split(',')[0].trim() : '';
                startMonth = startMonth?.split(' ')[0] ? startMonth.split(' ')[0].trim() : '';
                startMonth = monthMap[startMonth] ? monthMap[startMonth] : '';
                startYear = eventStartDate.split(',')[1] ? eventStartDate.split(',')[1].trim() : '';
              }

              if (eventEndDate) {
                endDay = eventEndDate.split(',')[0] ? eventEndDate.split(',')[0].trim() : '';
                endDay = endDay?.split(' ')[1] ? endDay.split(' ')[1].trim() : '';
                endDay = endDay.length === 1 ? '0' + endDay : endDay;
                endMonth = eventEndDate.split(',')[0] ? eventEndDate.split(',')[0].trim() : '';
                endMonth = endMonth?.split(' ')[0] ? endMonth.split(' ')[0].trim() : '';
                endMonth = monthMap[endMonth] ? monthMap[endMonth] : '';
                endYear = eventEndDate.split(',')[1] ? eventEndDate.split(',')[1].trim() : '';
              }

              const eventPath = !eventEndDate ? `/rsvp/?event=${eventTitle}&date=${startYear}-${startMonth}-${startDay}` : `/rsvp/?event=${eventTitle}&date=${startYear}-${startMonth}-${startDay}|${endYear}-${endMonth}-${endDay}`;
              const rsvpUrl = window.location.origin + eventPath;
              const htmlContent = `<br /><a href="${rsvpUrl}">RSVP</a> for this event.`;
              const newChildNode = document.createElement('p');
              newChildNode.className = 'rsvp-link';
              newChildNode.innerHTML = htmlContent;
              target.appendChild(newChildNode);
            }
          }
        }
      });
    });

    document.querySelectorAll(".simcal-event-details").forEach((element) => {
      observer.observe(element, { attributes: true, attributeFilter: ["style"] });
    });

    
    // Add the RSVP link dynamically to the calendar.
  };
}

// Add RSVP links to all events in the Simple Calendar: viewport < 467px.
const addRSVPLinkMobile = () => {
  document.querySelectorAll('.simcal-day-has-events').forEach(event => {
    event.addEventListener('click', function() {
      // Set up the MutationObserver to watch for the addition of the event bubble to the DOM.
      const observer = new MutationObserver(mutationsList => {
        mutationsList.forEach(mutation => {
          // Check if .simcal-event-bubble is added to the DOM.
          mutation.addedNodes.forEach(node => {  
            if (node.nodeType === 1 && node.matches('.simcal-event-bubble')) {
              const clickedDay = node.querySelector('.simcal-events');
              
              if (clickedDay && clickedDay?.children) {
                for (const child of clickedDay.children) {
                  const eventTitle = child?.firstElementChild?.innerText.split(' ').join('-').toLowerCase();
                  const eventDescription = child?.lastElementChild;

                  if (eventTitle && eventDescription && eventDescription?.children) {                    
                    for (const eventDetail of eventDescription.children) {
                      if (eventDetail?.firstElementChild && eventDetail.firstElementChild?.className && eventDetail.firstElementChild.className.includes('simcal-event-start')) {
                        const eventDates = eventDetail?.children;
                        let startDate = '', endDate = '';

                        // Set the start and end dates of the event.
                        if (eventDates) {
                          for (const date of eventDates) {
                            if (date?.className.includes('simcal-event-start-date')) startDate = date?.dataset?.eventStart;
                            else if (date?.className.includes('simcal-event-end-date')) endDate = date?.dataset?.eventStart;
                          }

                          const options = { timeZone: 'America/Los_Angeles', year: 'numeric', month: '2-digit', day: '2-digit' };

                          const formatToPacificTime = (timestamp) => {
                            if (!timestamp) return '';
                            
                            const date = new Intl.DateTimeFormat('en-CA', options).format(new Date(timestamp * 1000));
                            return date.replace(/(\d{2})-(\d{2})-(\d{4})/, '$3-$1-$2'); // Converts MM-DD-YYYY to YYYY-MM-DD
                          };

                          const startDateFormatted = formatToPacificTime(startDate);
                          const endDateFormatted = formatToPacificTime(endDate);
                          const eventPath = !endDateFormatted 
                            ? `/rsvp/?event=${encodeURIComponent(eventTitle)}&date=${encodeURIComponent(startDateFormatted)}`
                            : `/rsvp/?event=${encodeURIComponent(eventTitle)}&date=${encodeURIComponent(startDateFormatted)}|${encodeURIComponent(endDateFormatted)}`;                          
                          const rsvpUrl = window.location.origin + eventPath;
                          const htmlContent = `<br /><a href="${rsvpUrl}">RSVP</a> for this event.`;
                          const newChildNode = document.createElement('p');
                          
                          newChildNode.className = 'rsvp-link';
                          newChildNode.innerHTML = htmlContent;
                          eventDescription.appendChild(newChildNode);
                        }

                        break;
                      }
                    }
                  }
                }
              }
                
              // Disconnect the observer once it's done.
              observer.disconnect();
            }
          });
        });
      });
  
      // Start observing the entire document for added nodes.
      observer.observe(document.body, {
        childList: true,
        subtree: true,  // Watch the entire document subtree for changes.
      });
    });
  });       
};

// Correct the disabled attribute of the Simple Calendar based on the current months and start and end months. (The plugin doesn't do this accurately.)
if (typeof correctCalNavButtons === 'undefined') {
  var correctCalNavButtons = () => {
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
}

// Call the add-RSVP-link function every time a user navigates between months to ensure that the right calendar is being referenced.
if (typeof observeCurrentCalendarChanges === 'undefined') {
  var observeCurrentCalendarChanges = () => {
    const targetNode = document.querySelector('.simcal-current');

    if (!targetNode) return;

    const observer = new MutationObserver(mutations => {
      mutations.forEach(mutation => {
        if (mutation.attributeName === 'data-calendar-current') {
          addRSVPLinkDesktop();
          addRSVPLinkMobile();
          correctCalNavButtons();
        }
      });
    });

    observer.observe(targetNode, { attributes: true });
  };
}

// Open tabs on the Rides & Routes page dynamically on redirect from the About Us page and disable the default scrolling to the hash.
if (typeof tabRedirectAndScrollSupppression === 'undefined') {
  var tabRedirectAndScrollSupppression = () => {
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
}

// Hide the "Most Popular" tag on the find-your-plan page.
if (typeof hidePopularTag === 'undefined') {
  var hidePopularTag = () => {
    const mostPopularTag = document.querySelectorAll('.gp-level-badge');

    if (mostPopularTag) {
      Object.values(mostPopularTag).forEach(tag => {
        tag.style.display = 'none';
      });
    }
  }
}

// Disable the "Select" button for the Board Member level. (This occurs on two differenet pages, ergo two different removals.)
if (typeof disableBoardMemberSelect === 'undefined') {
  var disableBoardMemberSelect = () => {
    const pmproLevelsTable = document.querySelector('.pmpro_levels_table');
    if (pmproLevelsTable) {
      const tableRows = pmproLevelsTable.querySelectorAll('tr');
      if (tableRows) {
        Object.values(tableRows).forEach(row => {
          const membershipLevel = row.querySelectorAll('th');
          if (membershipLevel) {
            Object.values(membershipLevel).forEach(level => {
              const text = level.textContent;
              if (text === 'Board Member') {
                const selectButton = row.querySelector('.pmpro_btn');

                // Hide the board-member card.
                row.classList.add('gp-level-name-is-board-member');

                // Disable the select button.
                if (selectButton) selectButton.href = '';
              }
            });
          }
        });
      }
    }
    
    const pmproLevels = document.querySelectorAll('.gp-level');
    if (pmproLevels) {
      Object.values(pmproLevels).forEach(level => {
        const children = level.childNodes;

        if (children) {
          children.forEach(child => {
            if (child.className.includes('gp-level-name')) {
              const text = child.textContent;
              if (text === 'Board Member') {
                const selectButton = level.querySelector('.gp-level-checkout-button');

                // Hide the board-member card.
                level.classList.add('gp-level-name-is-board-member');

                // Disable the select button.
                if (selectButton) {
                  const button = selectButton.querySelector('a');
                  button.href = '';
                }
              }
            }
          });
        }
      });
    }
  }
}

// Truncate the FAQs text to 200 characters.
if (typeof truncateFaqs === 'undefined') {
  var truncateFaqs = () => {
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
}

// Hide the site's header in the login modal.
if (typeof hideHeaderInModal === 'undefined') {
  var hideHeaderInModal = () => {
    const targetModal = document.querySelector('.gp-popup-box');

    if (!targetModal) return;

    // Function to hide the header.
    const hideHeader = () => {
      const headerToHide = targetModal.querySelectorAll('.gp-header');

      if (headerToHide && headerToHide.length > 0) {
        headerToHide.forEach(header => {
          // header.style.visibility = 'hidden';
          header.style.display = 'none';
        });
      }
    };

    // Observe changes in the modal.
    const observer = new MutationObserver(() => {
      if (targetModal.classList.contains('animated') && targetModal.classList.contains('fadeIn')) {
        hideHeader();

        // Force the login form to submit when the modal is open. (This is a workaround for the PMPro AJAX form submission, which is broken.)
        const loginForm = document.querySelector('#loginform');
        if (loginForm) {
          loginForm.addEventListener('submit', (e) => {
            e.preventDefault(); // Ensure no AJAX interference
            loginForm.submit(); // Force native form submission
          });
        }
      }
    });

    // Start observing for content and attribute changes.
    observer.observe(targetModal, {
      childList: true,    // Watch for new child nodes (added/removed elements)
      subtree: true,     // Monitor all descendants of the target
      attributes: true,  // Detect class changes
      attributeFilter: ['class'] // Only watch for class changes
    });

    // Ensure the header is hidden if the modal is already open.
    if (targetModal.classList.contains('animated') && targetModal.classList.contains('fadeIn')) {
      hideHeader();
    }
  }
}

// Reposition the login button after a failed sign-in attempt.
if (typeof repositionLoginButton === 'undefined') {
  var repositionLoginButton = () => {
  // Get the button wrapper
    const submitButtonWrapper = document.querySelector('.gp-submit-button-wrapper');

    // Get the preceding sibling of the button wrapper
    const precedingSibling = submitButtonWrapper?.previousElementSibling;

    // Insert the button wrapper above its preceding sibling
    if (submitButtonWrapper && precedingSibling) {
      precedingSibling.parentNode.insertBefore(submitButtonWrapper, precedingSibling);
    }
  }
}

// Fix the value of the login_member_redirect hidden input field on the login form.
if (typeof fixLoginMemberRedirect === 'undefined') {
  var fixLoginMemberRedirect = () => {
    const redirectField = document.querySelector('input[name="login_member_redirect"]');
    
    if (redirectField) {
      const url = window.location.origin;
      redirectField.value = url;
    }
  }
}

// Format the "Learn More" button on the public-facing homepage.
const formatLearnMoreButton = () => {
  if (window.location.pathname === '/') {
    document.querySelectorAll('.gp-button').forEach(button => {
      if (button?.textContent === 'Learn More') {

        button.style.width = '17rem';
        button.style.fontSize = '2rem';
        button.style.fontWeight = 'bolder';
        button.style.padding = '2rem';
      }
    })
  }
}

// Format the RSVP page's title. (NOT IN USE: doing this on rsvp-functions.php, instead.)
const formatRsvpPageSubtitle = () => {
  const rsvpPageSubtitleElement = document.querySelector('.rsvp-page_subtitle');
  const rsvpPageSubtitle = rsvpPageSubtitleElement ? rsvpPageSubtitleElement?.innerHTML : '';
  const rsvpDates = rsvpPageSubtitle ? rsvpPageSubtitle.split('to') : '';
  const rsvpStartDate = rsvpDates && rsvpDates.length >= 1 ? rsvpDates[0].trim() : '';
  const rsvpEndDate = rsvpDates && rsvpDates.length >= 2 ? rsvpDates[1].trim() : '';
  
  if (rsvpEndDate) {
    // The format of the date arrays is [day, month, year].
    const rsvpStartDateArray = rsvpStartDate ? rsvpStartDate.split(' ') : [];
    const rsvpEndDateArray = rsvpEndDate ? rsvpEndDate.split(' ') : [];
    
    if (rsvpStartDateArray.length > 0 && rsvpEndDateArray.length > 0) {
      const rsvpStartDay = rsvpStartDateArray[0];
      const rsvpStartMonth = rsvpStartDateArray[1];
      const rsvpStartYear = rsvpStartDateArray[2];
      const rsvpEndDay = rsvpEndDateArray[0];
      const rsvpEndMonth = rsvpEndDateArray[1];
      const rsvpEndYear = rsvpEndDateArray[2];
      let revisedRsvpDate = '';
  
      if (rsvpStartYear === rsvpEndYear) {
        if (rsvpStartMonth === rsvpEndMonth) {
          revisedRsvpDate = `${rsvpStartDay} - ${rsvpEndDay} ${rsvpStartMonth} ${rsvpStartYear}`;
        } else {
          revisedRsvpDate = `${rsvpStartDay} ${rsvpStartMonth} - ${rsvpEndDay} ${rsvpEndMonth} ${rsvpStartYear}`;
        }
      }
  
      rsvpPageSubtitleElement.innerHTML = revisedRsvpDate;
    }
  }
};

// Call the functions when the DOM is fully loaded.
document.addEventListener('DOMContentLoaded', () => {
  addRSVPLinkDesktop();
  addRSVPLinkMobile();
  observeCurrentCalendarChanges();
  tabRedirectAndScrollSupppression();
  hidePopularTag();
  disableBoardMemberSelect();
  truncateFaqs();
  hideHeaderInModal();
  repositionLoginButton();
  fixLoginMemberRedirect();
  formatLearnMoreButton();
  // formatRsvpPageSubtitle();
});

// Override the scroll animation when there are only two rows in the Items block.
(function($) {
  // Override jQuery animate to block only scroll animations
  var originalAnimate = $.fn.animate;
  $.fn.animate = function (props, speed, easing, callback) {
    if (props.scrollTop !== undefined) {
      return this; // Block only scroll animations
    }
    return originalAnimate.apply(this, arguments);
  };      
})(jQuery);
