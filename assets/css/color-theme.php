<?php

$button_color = get_option('booked_button_color','#56c477');
$light_color = get_option('booked_light_color','#c4f2d4');
$dark_color = get_option('booked_dark_color','#039146');

?>

#ui-datepicker-div.booked_custom_date_picker table.ui-datepicker-calendar tbody td a.ui-state-active,
#ui-datepicker-div.booked_custom_date_picker table.ui-datepicker-calendar tbody td a.ui-state-active:hover,
body #booked-profile-page input[type=submit].button-primary:hover,
body .booked-list-view button.button:hover, body .booked-list-view input[type=submit].button-primary:hover,
body div.booked-calendar input[type=submit].button-primary:hover,
body .booked-modal input[type=submit].button-primary:hover,
body div.booked-calendar .bc-head,
body div.booked-calendar .bc-head .bc-col,
body div.booked-calendar .booked-appt-list .timeslot .timeslot-people button:hover,
body #booked-profile-page .booked-profile-header,
body #booked-profile-page .booked-tabs li.active a,
body #booked-profile-page .booked-tabs li.active a:hover,
body #booked-profile-page .appt-block .google-cal-button > a:hover,
#ui-datepicker-div.booked_custom_date_picker .ui-datepicker-header
{ background:<?php echo $light_color; ?> !important; }

body #booked-profile-page input[type=submit].button-primary:hover,
body div.booked-calendar input[type=submit].button-primary:hover,
body .booked-list-view button.button:hover, body .booked-list-view input[type=submit].button-primary:hover,
body .booked-modal input[type=submit].button-primary:hover,
body div.booked-calendar .bc-head .bc-col,
body div.booked-calendar .booked-appt-list .timeslot .timeslot-people button:hover,
body #booked-profile-page .booked-profile-header,
body #booked-profile-page .appt-block .google-cal-button > a:hover
{ border-color:<?php echo $light_color; ?> !important; }

body div.booked-calendar .bc-row.days,
body div.booked-calendar .bc-row.days .bc-col,
body .booked-calendarSwitcher.calendar,
body #booked-profile-page .booked-tabs,
#ui-datepicker-div.booked_custom_date_picker table.ui-datepicker-calendar thead,
#ui-datepicker-div.booked_custom_date_picker table.ui-datepicker-calendar thead th
{ background:<?php echo $dark_color; ?> !important; }

body div.booked-calendar .bc-row.days .bc-col,
body #booked-profile-page .booked-tabs
{ border-color:<?php echo $dark_color; ?> !important; }

#ui-datepicker-div.booked_custom_date_picker table.ui-datepicker-calendar tbody td.ui-datepicker-today a,
#ui-datepicker-div.booked_custom_date_picker table.ui-datepicker-calendar tbody td.ui-datepicker-today a:hover,
body #booked-profile-page input[type=submit].button-primary,
body div.booked-calendar input[type=submit].button-primary,
body .booked-list-view button.button, body .booked-list-view input[type=submit].button-primary,
body .booked-list-view button.button, body .booked-list-view input[type=submit].button-primary,
body .booked-modal input[type=submit].button-primary,
body div.booked-calendar .booked-appt-list .timeslot .timeslot-people button,
body #booked-profile-page .booked-profile-appt-list .appt-block.approved .status-block,
body #booked-profile-page .appt-block .google-cal-button > a,
body .booked-modal p.booked-title-bar,
body div.booked-calendar .bc-col:hover .date span,
body .booked-list-view a.booked_list_date_picker_trigger.booked-dp-active,
body .booked-list-view a.booked_list_date_picker_trigger.booked-dp-active:hover,
.booked-ms-modal .booked-book-appt,
body #booked-profile-page .booked-tabs li a .counter
{ background:<?php echo $button_color; ?>; }

body #booked-profile-page input[type=submit].button-primary,
body div.booked-calendar input[type=submit].button-primary,
body .booked-list-view button.button, body .booked-list-view input[type=submit].button-primary,
body .booked-list-view button.button, body .booked-list-view input[type=submit].button-primary,
body .booked-modal input[type=submit].button-primary,
body #booked-profile-page .appt-block .google-cal-button > a,
body div.booked-calendar .booked-appt-list .timeslot .timeslot-people button,
body .booked-list-view a.booked_list_date_picker_trigger.booked-dp-active,
body .booked-list-view a.booked_list_date_picker_trigger.booked-dp-active:hover
{ border-color:<?php echo $button_color; ?>; }

body .booked-modal .bm-window p i.fa,
body .booked-modal .bm-window a,
body .booked-appt-list .booked-public-appointment-title,
body .booked-modal .bm-window p.appointment-title,
.booked-ms-modal.visible:hover .booked-book-appt
{ color:<?php echo $button_color; ?>; }

.booked-appt-list .timeslot.has-title .booked-public-appointment-title { color:inherit; }
