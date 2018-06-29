<?php

if (!function_exists('responseJson')) {
    function responseJson($code, $message = '', $data = array(), $status = 200)
    {
        return response()->json(array(
            'code'          => $code,
            'message'       => $message,
            'data'          => $data,
            'status_code'   => $status,
        ));
    }
}

if (!function_exists('successJson')) {
    function successJson($message = '', $data = array(), $status = 200)
    {
        return responseJson('success', $message, $data, $status);
    }
}

if (!function_exists('errorJson')) {
    function errorJson($message = '', $data = array(), $status = 200)
    {
        return responseJson('error', $message, $data, $status);
    }
}

if (!function_exists('isMobile')) {
    function isMobile($value)
    {
        if (11 !== mb_strlen($value)) {
            return false;
        }

        $arr = array(
            130, 131, 132, 133, 134, 135, 136, 137, 138, 139,
            145, 146, 147, 148, 149,
            150, 151, 152, 153, 154, 155, 156, 157, 158, 159,
            166,
            171, 172, 173, 174, 175, 176, 177, 178,
            180, 181, 182, 183, 184, 185, 186, 187, 188, 189,
            198, 199,
        );

        return in_array(mb_substr($value, 0, 3), $arr);
    }
}

if (!function_exists('isEmail')) {
    function isEmail($email)
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) != false;
    }
}

if (!function_exists('buildUriPart')) {
    function buildUriPart($data)
    {
        if (is_array($data)) {
            $uriPart    = collect($data)->map(function ($str) {
                return trim($str, '/');
            })->implode('/');
        } else {
            $uriPart    = (string) $data;
        }

        return $uriPart;
    }
}

if (!function_exists('employeeUrl')) {
    function employeeUrl($uri)
    {
        return config('strongberry.employeeUrl') . '/' . buildUriPart($uri);
    }
}

if (!function_exists('customerUrl')) {
    function customerUrl($uri)
    {
        return config('strongberry.customerUrl') . '/' . buildUriPart($uri);
    }
}

if (!function_exists('isIdNumber')) {
    function isIdNumber($value)
    {
        $city = array(
            '11', '12', '13', '14', '15', '21', '22',
            '23', '31', '32', '33', '34', '35', '36',
            '37', '41', '42', '43', '44', '45', '46',
            '50', '51', '52', '53', '54', '61', '62',
            '63', '64', '65', '71', '81', '82', '91',
        );

        if (!preg_match('/^([\d]{17}[xX\d]|[\d]{15})$/', $value)) {
            return false;
        }

        if (!in_array(substr($value, 0, 2), $city)) {
            return false;
        }

        $value  = preg_replace('/[xX]$/i', 'a', $value);
        $length = strlen($value);

        if ($length == 18) {
            $birthday = substr($value, 6, 4) . '-' . substr($value, 10, 2) . '-' . substr($value, 12, 2);
        } else {
            $birthday = '19' . substr($value, 6, 2) . '-' . substr($value, 8, 2) . '-' . substr($value, 10, 2);
        }

        if (date('Y-m-d', strtotime($birthday)) != $birthday) {
            return false;
        }

        if ($length == 18) {
            $sum = 0;
            for ($i = 17; $i >= 0; $i--) {
                $substr = substr($value, 17 - $i, 1);
                $sum    += (pow(2, $i) % 11) * (($substr == 'a') ? 10 : intval($substr, 11));
            }

            if($sum % 11 != 1) {
                return false;
            }
        }

        return true;
    }
}
