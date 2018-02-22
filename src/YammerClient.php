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

      $this->content = json_decode($this->body->getBody(), TRUE)['messages'];

      return $this;
  }

  public function withUsers()
  {
      $references = json_decode($this->body->getBody(), TRUE)['references'];

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

  private function _search($find = '', $column = '', $dataset = [])
  {
      foreach ($dataset as $key => $data) {
          if ($data[$column] == $find) {
              return $data;
          }
      }
      return null;
  }
}
