<?php
return array(
    'controllers' => array(
        'invokables' => array(
            'IosTest\Controller\IosTest' => 'IosTest\Controller\IosTestController',
        ),
    ),
    'view_manager' => array(
        'template_path_stack' => array(
            'album' => __DIR__ . '/../view',
        ),
    ),
);