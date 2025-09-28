<?php

return [
    'title' => 'My notes',
    'subtitle' => 'Capture quick reminders and stay on top of your tasks.',

    'actions' => [
        'new' => 'Add note',
        'create' => 'Save note',
        'edit' => 'Edit note',
        'update' => 'Update note',
        'cancel' => 'Cancel',
    ],

    'fields' => [
        'title' => 'Title',
        'content' => 'Details',
        'reminder_at' => 'Reminder at',
        'status' => 'Status',
        'actions' => 'Actions',
    ],

    'status' => [
        'due' => 'Reminder due',
        'upcoming' => 'Upcoming reminder',
        'completed' => 'Completed',
        'no_reminder' => 'No reminder',
    ],

    'helpers' => [
        'reminder_at' => 'Leave empty if you do not need a reminder.',
    ],

    'messages' => [
        'created' => 'Note created successfully.',
        'updated' => 'Note updated successfully.',
        'deleted' => 'Note deleted successfully.',
        'completed' => 'Great! The note was marked as completed.',
        'reopened' => 'The note is active again.',
    ],

    'confirmations' => [
        'delete' => 'Are you sure you want to delete this note?',
        'complete' => 'Mark this note as completed?',
        'reopen' => 'Reopen this note?',
    ],

    'empty' => 'You have not created any notes yet.',

    'results_count' => 'Results: :count',

    'notifications' => [
        'title' => 'Notes',
        'due_today' => 'Reminder due today',
        'due_now' => 'Reminder due now',
        'overdue' => '{1} Reminder overdue by 1 day|[2,*] Reminder overdue by :count days',
        'upcoming' => '{1} Reminder in 1 day|[2,*] Reminder in :count days',
        'reminder_on' => 'Reminder on :date',
        'view_all' => 'Manage notes',
    ],
];
