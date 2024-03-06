<?php

namespace App\Controllers;

class Api extends BaseController {
	private $conditions = array(
		array(
			"id" => "nameId",
			"condition" => "equals",
			"value" => "Bobby"
		),
		array(
			"id" => "birthdayId",
			"condition" => "greater_than",
			"value" => "2024-01-23T05:01:47.691Z"
		)
	);

	public function __construct() {}

	private function getCompare($find, $question, $condition) {

		if (strtotime($find) && !strtotime($question)) {
			$find = date('Y-m-d', strtotime($find));
			$question = date('Y-m-d', strtotime($question));
		}


		switch ($condition) {
			case 'equals':
				if ($find == $question) {
					return true;
				} else {
					return false;
				}
			case 'does_not_equal':
				if ($find != $question) {
					return true;
				} else {
					return false;
				}
			case 'greater_than':
				if ($question > $find) {
					return true;
				} else {
					return false;
				}
			case 'less_than':
				if ($question < $find) {
					return true;
				} else {
					return false;
				}
			default:
				return false;
		}
	}

	private function doFilter($questions) {

		for ($count=0; $count<count($this->conditions); $count++) {
			$testedFalse = false;
			for ($questionCount=0; $questionCount<count($questions); $questionCount++) {
				$find = $this->conditions[$count]['value'];
				$condition = $this->conditions[$count]['condition'];
				$question = $questions[$questionCount]->value;
				if ($this->getCompare($find, $question, $condition)) {
					$questions[$questionCount]->id = $this->conditions[$count]['id'];
					$testedFalse = true;
					break;
				}
				$testedFalse = false;
			}
			if (!$testedFalse) {
				break;
			}
		}

		return $questions;
	}

    public function filteredResponses() {
    	$apiKey = 'sk_prod_TfMbARhdgues5AuIosvvdAC9WsA5kXiZlW8HZPaRDlIbCpSpLsXBeZO7dCVZQwHAY3P4VSBPiiC33poZ1tdUj2ljOzdTCCOSpUZ_3912';
        $formId = 'cLZojxk94ous';
        $url = 'https://api.fillout.com/v1/api/forms/'.$formId.'/submissions';

        $client = \Config\Services::curlrequest();
        $headers = [
        	'Authorization' => 'Bearer ' . $apiKey
        ];
        $response = $client->request('GET', $url, ['headers' => $headers]);

		$resultObject = json_decode($response->getBody());

		$resultArray = [];
		$regex = "/.*Id$/i";

		foreach ($resultObject->responses as $response) {
			$questions = $response->questions;
			$resultQuestions = $this->doFilter($questions);
			$readyToAdd = false;
			foreach ($resultQuestions as $question) {
				if (preg_match($regex, $question->id)) {
					$readyToAdd = true;
				}
			}
			if ($readyToAdd) {
				$response->questions = $resultQuestions;
				if ($response->questions != null) {
					array_push($resultArray, $response);
				}
			}
		}

		return json_encode($resultArray);
    }
}