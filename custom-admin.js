// Toggle the Delete Selected button on the RSVPs' dashboard event's page.
const toggleDeleteButton = () => {
  const checkboxes = document.querySelectorAll('input[name="rsvp_ids[]"]');
  const rsvpDeleteButton = document.querySelector('.delete-selected-rsvps');
  const anyChecked = checkboxes && checkboxes.length > 0 ? Array.from(checkboxes).some(checkbox => checkbox.checked) : false;

  if (rsvpDeleteButton) {
    rsvpDeleteButton.style.display = anyChecked ? 'block' : 'none';
  }
}

// Add event listeners to checkboxes and select all checkbox.
const rsvpCheckboxListener = () => {
  const checkboxes = document.querySelectorAll('input[name="rsvp_ids[]"]');
  const selectAllCheckbox = document.querySelector('input[id="select-all"]');
  
  // Add event listeners to checkboxes.
  if (checkboxes && checkboxes.length > 0) {
    checkboxes.forEach(checkbox => {
      checkbox.addEventListener('change', toggleDeleteButton);
    });
  }

  // Add event listener to select all checkbox.
  if (selectAllCheckbox) {
    selectAllCheckbox.addEventListener('change', toggleDeleteButton);
  }
}

// Add event listener to the document to run the functions when the page is loaded.
document.addEventListener('DOMContentLoaded', () => {
  rsvpCheckboxListener();
  toggleDeleteButton();
});
