<?php
  namespace vilshub\validator;
  use vilshub\helpers\message;
  use \Exception;
  use vilshub\helpers\get;
  use vilshub\helpers\style;
  use vilshub\helpers\textProcessor;
  /**
   *
   */
  class validate
  {
    public static function input($data, $rule){
      $supportedRules1 = ["required", "integer", "file", "string", "date", "email", "float"];
      $supportedRules2 = ["min-length:", "max-length:", "max-size:", "min-size:",  "ext:", "max-value:", "min-value:"];
      $selectedRules = [];
      $ruleData = [];
      $choosenDataType= null;
      $trimmedRules = str_replace(" ", "", $rule);
      $rulesArray = explode("|", $trimmedRules);
      $total = count($rulesArray);

      try{
        if(!is_string($rule)){
          throw new Exception("static method argument 1 must be a string");
        }elseif ($total >= 1){
          for ($x=0;$x<=$total-1;$x++){
            $fit = strtolower($rulesArray[$x]);
            if(!in_array($fit, $supportedRules1)){//Non data rule field
              $explodable = substr_count($rulesArray[$x], ":");
              $totalCharactersNumber = strlen($rulesArray[$x]);
              if($explodable == 0 || $explodable > 1 || $totalCharactersNumber < 3){
                throw new Exception("static method argument 2 supplied rule '".style::color($rulesArray[$x], "black")."' not supported");
              }else if ($explodable == 1 && $totalCharactersNumber >=3) { //Extractable
                $getParts = explode(":", $rulesArray[$x]);
                $id = strtolower($getParts[0]);

                if(!in_array($id.":", $supportedRules2)){
                  throw new Exception("static method argument 2 supplied rule '".style::color($rulesArray[$x] , "black")."' not supported");
                }else {
                  //check value
                  if($id == "min-length" || $id == "max-length" || $id == "max-size" || $id == "min-size"){
                    //value must be integer
                    $value = intval($getParts[1]);
                    if(is_integer($value) ){
                      if($value<0){
                        throw new Exception("static method argument 2 supplied rule, '".style::color($id , "black")."' value must be positive integer, '".style::color($value , "black")."' is not a positive integer");
                      }else {
                        if(in_array($id, $selectedRules)){
                            throw new Exception("static method argument 2 supplied rule, '".style::color($id , "black")."' repeated");
                        }

                        $selectedRules[] = $id;
                        $ruleData[$id] = $value;
                      }
                    }else {
                      throw new Exception("static method argument 2 supplied rule, '".style::color($id , "black")."' value must be an integer, '".style::color($value , "black")."' is not an integer");
                    }
                  }elseif ($id == "ext") {
                    $extension = $getParts[1];
                    //check if alphanumeric
                    if(textProcessor::is_alpha_numeric($extension)){
                      if(in_array($id, $selectedRules)){
                          throw new Exception("static method argument 2 supplied rule, '".style::color($id , "black")."' repeated");
                      }
                      $selectedRules[] = $id;
                      $ruleData[$id] = $extension;
                    }else{
                      throw new Exception("static method argument 2 supplied rule, '".style::color($id , "black")."' value must be an alphaNumeric, '".style::color($extension , "black")."' is not alphaNumeric");
                    }
                  }else if($id == "min-value" || $id == "max-value"){
                    if(is_integer(intval($getParts[1]))){
                      $value = intval($getParts[1]);
                    }elseif (is_float(floatval($getParts[1]))) {
                      $value = floatval($getParts[1]);
                    }else {
                      throw new Exception("static method argument 2 supplied rule, '".style::color($id , "black")."' value must either be an integer or a float, '".style::color($value , "black")."' is neither an integer or a float");
                    }

                    if(isset($ruleData["min-value"])){
                      if($ruleData["min-value"] > $value){
                        throw new Exception("static method argument 2 supplied rule, '".style::color($id , "black")."' value cannot be less than '".style::color("min:value", "black")."' value '".style::color($ruleData["min-value"], "black")."'");
                      }
                    }elseif (isset($ruleData["max-value"])) {
                      if($ruleData["max-value"] < $value){
                        throw new Exception("static method argument 2 supplied rule, '".style::color($id , "black")."' value cannot be greater than '".style::color("max:value", "black")."' value '".style::color($ruleData["max-value"], "black")."'");
                      }
                    }
                    if(in_array($id, $selectedRules)){
                        throw new Exception("static method argument 2 supplied rule, '".style::color($id , "black")."' repeated");
                    }
                    $selectedRules[] = $id;
                    $ruleData[$id] = $value;
                    // }
                  }
                }
              }
            }else{
              //Check for datatype conflct
              if($choosenDataType != null && ($fit == "string" || $fit == "float" || $fit == "integer" || $fit == "file")){
                throw new Exception("static method argument 2 supplied rule, '".style::color($fit , "black")."' datatype conflicts with the 1st specified datatype '".style::color($choosenDataType, "black")."'");
              }

              //Check for repeatation
              if(in_array($fit, $selectedRules)){
                throw new Exception("static method argument 2 supplied rule, '".style::color($fit , "black")."' repeated");
              }

              if(!in_array($fit, $selectedRules)){
                $selectedRules[] = $fit;
                if($fit == "integer" || $fit == "float" || $fit == "string" || $fit == "file"){
                  $choosenDataType = $fit;
                }
              }

            }
          }
        }
      }catch (Exception $e){
        die(message::write("error", get::staticMethod(__CLASS__, __FUNCTION__). $e->getMessage()));
      };

      function check_reuired($value, $bank){
        if(empty($value)){
          return "e".array_search("required", $bank);
        }else {
          return TRUE;
        };
      }

      ///Begin validation
      //Validate string
      if(in_array("string", $selectedRules)){
        //check if required
        if(in_array("required", $selectedRules)){
            if(check_reuired($data, $selectedRules) !== TRUE){
              return check_reuired($data, $selectedRules);
            }
        };

        //check if it's a string
        if(!is_string($data)){
          return "e".array_search("string", $selectedRules);
        };


        //check max-length
        if(in_array("max-length", $selectedRules)){
          if(strlen($data) > $ruleData["max-length"]){
            return "e".array_search("max-length", $selectedRules);
          };
        };

        //check min-length
        if(in_array("min-length", $selectedRules)){
          if(strlen($data) < $ruleData["min-length"]){
            return "e".array_search("min-length", $selectedRules);
          };
        };

        //check string format
        //email
        if(in_array("email", $selectedRules)){
          if(!filter_var($data, FILTER_VALIDATE_EMAIL)){
            return "e".array_search("email", $selectedRules);
          };
        };
      };

      //Validate integer
      if(in_array("integer", $selectedRules) || in_array("float", $selectedRules)){
        //check if required
        //check if it's an integer
        if(in_array("integer", $selectedRules)){
          if(!is_integer($data)){
            return "e".array_search("integer", $selectedRules);
          };
        }elseif (in_array("float", $selectedRules)) {
          if(!is_float($data)){
            return "e".array_search("float", $selectedRules);
          };
        }


        //check minimum value
        if(in_array("min-value", $selectedRules)){
          if($data < $ruleData["min-value"]){
            return "e".array_search("min-value", $selectedRules);
          };
        }

        //check maximum value
        if(in_array("max-value", $selectedRules)){
          if($data > $ruleData["max-value"]){
            return "e".array_search("max-value", $selectedRules);
          };
        }
      }

      //Validate file
      //manipulations here
      return true;
    }
    public static function arrayVariable($value, $msg){
      if(is_array($value)){
        return true;
      }else{
        die($msg);
      }
    }
    public static function stringVariable($value, $msg){
      if(is_string($value)){
        return true;
      }else{
        die($msg);
      }
    }
    public static function booleanVariable($value, $msg){
      if(is_bool($value)){
        return true;
      }else{
        die($msg);
      }
    }
    public static function functionVariable($value, $msg){
      if(is_callable($value)){
        return true;
      }else{
        die($msg);
      }
    }
  }


?>
