<?php
$functions = [
    'local_gpbwebservice_bearbeite_anfragen' => [
        // The name of the namespaced class that the function is located in.
        'classname'   => 'local_gpbwebservice\bearbeite_anfragen',
        // A brief, human-readable, description of the web service function.
        'description' => 'GPB Webservice',
        // Options include read, and write.
        'type'        => 'write',
        // Whether the service is available for use in AJAX calls from the web.
        'ajax'        => true,
        // An optional list of services where the function will be included.
        'services' => [
            // A standard Moodle install includes one default service:
            // - MOODLE_OFFICIAL_MOBILE_SERVICE.
            // Specifying this service means that your function will be available for
            // use in the Moodle Mobile App.
            // MOODLE_OFFICIAL_MOBILE_SERVICE,
            'local/gpbwebservice'
        ]
        // A comma-separated list of capabilities used by the function.
        // This is advisory only and used to indicate to the administrator configuring a custom service definition.
        //'capabilities' => 'moodle/course:createcourses,moodle/course:managecourses',
    ],
];
?>