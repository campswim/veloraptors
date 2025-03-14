<?php

// Format the event's title from its URL slug.
function format_event_titles( $title ) {
  if ( !$title ) return;
  return $formatted = ucwords( implode( ' ', explode( '-', $title ) ) );
}

// Format the event's date from its URL slug for headings.
function format_event_date( $date ) {
  $formattedDate = new DateTime( $date );
  return $formattedDate ? $formattedDate->format( 'j M Y' ) : $date;
}

// Format the event's date to be more readable.
function format_event_date_readable( $date ) {
  $formattedDate = date( 'j F Y', strtotime( $date ) );
  return $formattedDate;
}