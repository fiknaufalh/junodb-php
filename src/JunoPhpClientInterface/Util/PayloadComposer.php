<?php
namespace JunoPhpClient\Util;

class PayloadCompressor {
    public static function compress($data) {
        return gzcompress($data);
    }

    public static function decompress($data) {
        return gzuncompress($data);
    }
}
