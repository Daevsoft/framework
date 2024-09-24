<?php

namespace Ds\Foundations\Network;

use Ds\Dir;
use Ds\Foundations\Validator\Validator;

abstract class RequestAbstract
{
    protected function rules()
    {
        return null;
    }
    public function validated($rule = null)
    {
        $rule = $rule ?? $this->rules();
        if ($rule == null) {
            return true;
        }
        // do validation
        Validator::make($this->all(), $rule);
    }
    public function all()
    {
        return [];
    }
}

// #[AllowDynamicProperties]
class Request extends RequestAbstract
{
    public static function getBack()
    {
        header('Location: ' . self::getReferrer());
    }
    public static function getReferrer()
    {
        $track = self::getTrackRoute();
        return $track[1] ?? $_SERVER['REQUEST_URI'];
    }
    public static function trackRoute()
    {
        $track = self::getTrackRoute();
        if (count($track) > 1) {
            unset($track[0]);
        }
        $track[] = $_SERVER['REQUEST_URI'];
        session(['track_route' => serialize($track)]);
    }
    public static function getTrackRoute()
    {
        $track = session('track_route');
        if ($track == null) {
            $track = [];
        } else {
            $track = unserialize($track);
        }
        unsession('track_route');
        return $track;
    }
    public function json()
    {
        return json_decode(file_get_contents('php://input'));
    }
    public function all()
    {
        return $_REQUEST;
    }
    public function __get($name)
    {
        switch ($name) {
            case 'headers':
                return getallheaders();
            default:
                return $this->getField($name);
        }
    }
    private function getField($name)
    {
        $value = $_REQUEST[$name] ?? null;
        if ($value == null) {
            return $value;
        }
        if (is_string($value)) {
            return trim(htmlspecialchars($value));
        }
        return $value;
    }
    public function add($propName, $value)
    {
        $this->{$propName} = $value;
    }
    public function old($key)
    {
        return flash('_old_' . $key);
    }
    public function setOld($key, $value)
    {
        return set_flash('_old_' . $key, $value);
    }
    public function files($field)
    {
        $result = [];
        $keys = array_keys($_FILES[$field]["name"]);
        foreach ($keys as $key) {
            $filename = md5(time() . basename($_FILES[$field]["name"][$key]));
            $target_dir = Dir::$MAIN . 'public/uploads/';
            $target_file = $target_dir . $filename;
            $uploadOk = 1;
            $fileExt = strtolower(pathinfo($_FILES[$field]["name"][$key], PATHINFO_EXTENSION));
            if (in_array($fileExt, ['.bat', '.sh', '.exe', '.apk'])) {
                $result[$key] = null;
                $uploadOk = 0;
            }

            // Check file size
            if ($_FILES[$field]["size"][$key] > 500000) {
                echo "Sorry, your file is too large.";
                $uploadOk = 0;
            }
            if ($uploadOk == 0) {
                echo "Sorry, your file was not uploaded.";
                $result[$key] = null;
                // if everything is ok, try to upload file
            } else {
                if (move_uploaded_file($_FILES[$field]["tmp_name"][$key], $target_file . '.' . $fileExt)) {
                    $result[$key] = $filename . '.' . $fileExt;
                } else {
                    $result[$key] = null;
                }
            }
        }
        return $result;
    }
    public function file($field, $filename = null)
    {
        if (!isset($_FILES['attachment'])) return null;
        if($filename == null){
            $random_number = rand();
            $filename = md5(time() . '_' . $random_number);
        }

        $filename = $filename ?? basename($_FILES[$field]["name"]);
        $target_dir = Dir::$MAIN . 'public/uploads/';
        $target_file = $target_dir . $filename;
        $uploadOk = 1;
        $fileExt = strtolower(pathinfo($_FILES[$field]["name"], PATHINFO_EXTENSION));
        // Check if image file is a actual image or fake image
        if (isset($_POST["submit"])) {
            if (in_array($fileExt, ['.bat', '.sh', '.exe', '.apk'])) {
                $uploadOk = 0;
            }
            // Check file size
            if ($_FILES[$field]["size"] > 500000) {
                echo "Sorry, your file is too large.";
                $uploadOk = 0;
            }
        }
        if ($uploadOk == 0) {
            echo "Sorry, your file was not uploaded.";
            // if everything is ok, try to upload file
        } else {
            if (move_uploaded_file($_FILES[$field]["tmp_name"], $target_file . '.' . $fileExt)) {
                return $filename . '.' . $fileExt;
            } else {
                return null;
            }
        }
    }
}
