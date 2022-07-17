<?php
/** @var int Means the exception is manually expected. */
const EX_CODE_EXPECTED = 0X01;

/** @var int Means the exception should be taken seriously. This is highlighted in database indexing. */
const EX_CODE_IMPORTANT = 0X02;

/** @var int Means the exception will be shown directly to the client without logging.
 * SECURITY WARNING: Only show the details in debug mode or to a trusted administrator! */
const EX_CODE_SHOWDETAILS = 0X04;
