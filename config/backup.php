<?php

return [

    'time' => env('BACKUP_TIME', '02:00'),

    'retention_days' => (int) env('BACKUP_RETENTION_DAYS', 7),

];
