<?php

namespace JunoPhpClient\Util;

enum JunoLogLevel: string
{
    case DEFAULT = 'WARNING';
    case DEBUG = 'DEBUG';
    case INFO = 'INFO';
    case WARN = 'WARNING';
    case ERROR = 'ERROR';
    case FATAL = 'CRITICAL';
    case CONFIG = 'INFO';
    case FINE = 'DEBUG';
    case FINER = 'DEBUG';
    case FINEST = 'DEBUG';
    case ALL = 'DEBUG';
    case OFF = 'EMERGENCY';
}