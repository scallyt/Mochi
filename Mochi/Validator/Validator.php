<?php
namespace Mochi\Validator;

class Validator
{
    private $errors = [];

    public function validate($data, $rules)
    {
        foreach ($rules as $field => $fieldRules) {
            if (strpos($field, '.messages') !== false) {
                continue;
            }

            foreach ($fieldRules as $rule => $ruleValue) {
                $value = isset($data[$field]) ? $data[$field] : null;
                $method = 'validate' . ucfirst($rule);

                $customMessages = isset($rules[$field . '.messages']) ? $rules[$field . '.messages'] : [];
                $customMessage = isset($customMessages[$rule]) ? $customMessages[$rule] : null;

                if (method_exists($this, $method)) {
                    $this->$method($field, $value, $ruleValue, $customMessage);
                }
            }
        }
    }

    public function getErrors()
    {
    $flatErrors = [];
    foreach ($this->errors as $fieldErrors) {
        foreach ($fieldErrors as $error) {
            $flatErrors[] = $error;
        }
    }
    return $flatErrors;
    }

    public function getErrorsAndHandle()
    {
        return $this->errors;
    }

    private function addError($field, $message)
    {
        $this->errors[$field][] = $message;
    }

    private function validateMin($field, $value, $min, $customMessage = null)
    {
        if (is_numeric($value) && floatval($value) < floatval($min)) {
            $this->addError($field, $customMessage ?? "The $field must be at least $min.");
        } elseif (is_string($value) && strlen($value) < $min) {
            $this->addError($field, $customMessage ?? "The $field must be at least $min characters.");
        }
    }

    private function validateMax($field, $value, $max, $customMessage = null)
    {
        if (is_numeric($value) && floatval($value) > floatval($max)) {
            $this->addError($field, $customMessage ?? "The $field must be no more than $max.");
        } elseif (is_string($value) && strlen($value) > $max) {
            $this->addError($field, $customMessage ?? "The $field must be no more than $max characters.");
        }
    }

    private function validateEmail($field, $value, $ruleValue, $customMessage = null)
    {
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->addError($field, $customMessage ?? "The $field must be a valid email address.");
        }
    }

    private function validateUrl($field, $value, $ruleValue, $customMessage = null)
    {
        if (!filter_var($value, FILTER_VALIDATE_URL)) {
            $this->addError($field, $customMessage ?? "The $field must be a valid URL.");
        }
    }

    private function validateRequired($field, $value, $ruleValue, $customMessage = null)
    {
        if (empty($value)) {
            $this->addError($field, $customMessage ?? "The $field must be filled.");
        }
    }

    private function validateDataType($field, $value, $dataType, $customMessage = null)
    {
        $types = [
            'integer'   => 'is_int',
            'float'     => 'is_float',
            'string'    => 'is_string',
            'boolean'   => 'is_bool',
            'array'     => 'is_array',
            'object'    => 'is_object',
            'null'      => 'is_null',
        ];

        if (!isset($types[$dataType])) {
            throw new \InvalidArgumentException("Unknown data type: $dataType");
        }

        $typeCheckFunction = $types[$dataType];

        if (!$typeCheckFunction($value)) {
            $this->addError($field, $customMessage ?? "The $field must be of type $dataType.");
        }
    }

    private function validateDate($field, $value, $format = 'Y-m-d', $customMessage = null)
    {
        $date = \DateTime::createFromFormat($format, $value);
        if ($date === false || $date->format($format) !== $value) {
            $this->addError($field, $customMessage ?? "The $field must be a valid date in the format $format.");
        }
    }

    private function validateBetween($field, $value, $between, $customMessage = null)
    {
        list($min, $max) = explode("|", $between);

        $min = (int)$min;
        $max = (int)$max;

        if (is_numeric($value)) {
            $value = (int)$value;
            if ($value < $min) {
                $this->addError($field, $customMessage ?? "The $field must be at least $min.");
            }
            if ($value > $max) {
                $this->addError($field, $customMessage ?? "The $field must be no more than $max.");
            }
        } elseif (is_string($value)) {
            $length = strlen($value);
            if ($length < $min) {
                $this->addError($field, $customMessage ?? "The $field must be at least $min characters.");
            }
            if ($length > $max) {
                $this->addError($field, $customMessage ?? "The $field must be no more than $max characters.");
            }
        }
    }


    public function validateInput($input, $rules)
    {
        $validator = new Validator();

        $data = json_decode($input, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            parse_str($input, $data);
        }

        $validator->validate($data, $rules);

        return $validator->getErrors();
    }
}
