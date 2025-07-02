document.addEventListener('DOMContentLoaded', function () {
  function maybeSubmitUpdate(input) {
    const rsvpId = input.dataset.rsvpId;
    const plusGuests = input.value;

    if (plusGuests === input.dataset.originalValue) {
      return; // no change
    }

    fetch(rsvpInline.ajax_url, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
      },
      body: new URLSearchParams({
        action: 'update_plus_guests',
        nonce: rsvpInline.nonce,
        rsvp_id: rsvpId,
        plus_guests: plusGuests
      })
    })
    .then(res => res.json())
    .then(response => {
      if (response.success) {
        input.dataset.originalValue = plusGuests;

        // Remove any existing checkmark wrapper
        const existingWrapper = input.parentElement.querySelector('.rsvp-checkmark-wrapper');
        if (existingWrapper) existingWrapper.remove();

        // Create a wrapper span
        const wrapper = document.createElement('span');
        wrapper.className = 'rsvp-checkmark-wrapper';
        wrapper.style.cssText = `
          display: inline-block;
          width: 1.5em;
          margin-left: 0.25em;
          vertical-align: middle;
          text-align: center;
        `;

        // Create the checkmark
        const checkmark = document.createElement('span');
        checkmark.textContent = '✔';
        checkmark.style.cssText = `
          color: green;
          font-weight: bold;
          font-size: 1.2em;
        `;

        wrapper.appendChild(checkmark);
        input.parentElement.appendChild(wrapper);

        // Remove after 2 seconds
        setTimeout(() => wrapper.remove(), 2000);
      } else {
        alert('Failed to update guest count.');
      }
    })
    .catch(() => {
      alert('Error updating guest count.');
    });
  }

  document.querySelectorAll('.inline-plus-guests-input').forEach(input => {
    // Store original value
    input.dataset.originalValue = input.value;

    // Blur event.
    input.addEventListener('blur', function () {
      maybeSubmitUpdate(this);
    });

    // The enter key triggers the update.
    input.addEventListener('keydown', function (e) {
      if (e.key === 'Enter') {
        e.preventDefault();
        this.blur(); // triggers blur handler
      }
    });
  });
});