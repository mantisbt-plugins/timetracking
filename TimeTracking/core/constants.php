<?php
namespace TimeTracking;

# define error codes
define( 'TimeTracking\ERROR_INVALID_TIME_FORMAT', 1 );
define( 'TimeTracking\ERROR_ID_NOT_EXISTS', 2 );

# Tokens table use a integer token type. To define a integer number that would not
# make collision with other keys, we use the result of crc32 of constant name
# crc32( 'TimeTracking\TOKEN_STOPWATCH_STATUS' )
define( 'TimeTracking\TOKEN_STOPWATCH_STATUS', 1732303295 );

define( 'TimeTracking\STOPWATCH_STOPPED', 0 );
define( 'TimeTracking\STOPWATCH_RUNNING', 1 );

# stopwatch life time is 24 hours
define( 'TimeTracking\STOPWATCH_EXPIRY', 3600 * 24 );