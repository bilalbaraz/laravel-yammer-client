<?php

namespace Yammer;

/**
 *
 */
class YammerClient
{

  const API_URL = 'https://www.yammer.com/api/';
  const VERSION = 'v1';
  private $client_id;
  private $token;
  private $httpClient;
  private $response;
  private $content;
  private $body;

  function __construct()
  {
      $this->client_id = config('yammer.client_id');
      $this->token = config('yammer.token');
      $this->httpClient = new \GuzzleHttp\Client();
  }

  public function getMessages($options = [])
  {
      $this->body = $this->httpClient->request(
        'GET',
        $this->_getFullUrl().'/messages.json?' . http_build_query($options), [
        'headers' => [
          'Authorization' => 'Bearer ' . $this->token
        ]
      ]);

      $this->body = $this->_jsonToArray($this->body->getBody());
      $this->content = $this->getBody('messages');

      return $this;
  }

  public function getMessagesByGroupId($groupId = null, $options = [])
  {
      if (is_null($groupId)) {
        throw new \Exception( __FUNCTION__ . " expects a group ID", 200);
      }

      $this->body = $this->httpClient->request(
        'GET',
        $this->_getFullUrl().'/messages/in_group/' . $groupId . '.json?' . http_build_query($options), [
        'headers' => [
          'Authorization' => 'Bearer ' . $this->token
        ]
      ]);

      $this->body = $this->_jsonToArray($this->body->getBody());
      $this->content = $this->getBody('messages');
      return $this;
  }

  public function getBody($key = '', $type = null)
  {
      $result = $dataset = $this->body[$key];

      if (!is_null($type)) {
          $result = $this->_search($type, 'type', $dataset);
      }

      return $result;
  }

  public function withUsers()
  {
      $references = $this->getBody('references', 'user');

      $users = array_filter($references, function($item) {
          return $item['type'] == 'user';
      });

      $this->content = array_map(function($message) use ($users) {

          $message['user'] = $this->_search($message['sender_id'], 'id', $users);

          if ($message['liked_by']['count'] > 0) {
              foreach ($message['liked_by']['names'] as $likeKey => $name) {
                  $message['liked_by']['names'][$likeKey]['user'] = $this->_search($name['user_id'], 'id', $users);
              }
          }

          if (count($message['notified_user_ids']) > 0) {
            foreach ($message['notified_user_ids'] as $notifyKey => $notifiedUserId) {
                $message['notified_user_ids'][$notifyKey] = $this->_search($notifiedUserId, 'id', $users);
            }
          }

          return $message;
      }, $this->content);

      return $this;
  }

  public function where($search_key = '', $search_value = '')
  {
      $this->content = array_where($this->content, function($value, $key) use ($search_key, $search_value) {
          return $value[$search_key] == $search_value;
      });

      return $this;
  }

  public function getStatusCode()
  {
      return $this->response->getStatusCode();
  }

  public function excludeReplies()
  {
      $this->content = array_where($this->content, function($value, $key) {
          return $value['replied_to_id'];
      });
      return $this;
  }

  public function onlyReplies()
  {
      $this->content = array_where($this->content, function($value, $key) {
          return !$value['replied_to_id'];
      });
      return $this;
  }

  public function get()
  {
      return $this->content;
  }

  private function _getFullUrl()
  {
      return self::API_URL.self::VERSION;
  }

  private function _jsonToArray($dataset)
  {
    return json_decode($dataset, TRUE);
  }

  private function _search($find = '', $column = '', $dataset = [])
  {
      $result = [];

      foreach ($dataset as $key => $data) {
          if ($data[$column] == $find) {
              array_push($result, $data);
          }
      }

      if(count($result) > 1){
          return $result;
      }else if (count($result) == 1) {
          return current($result);
      }else {
          return null;
      }
  }
}
