<?php

namespace Yammer;


/**
 * Class YammerClient
 * @package Yammer
 */
class YammerClient
{

    /**
     *
     */
    const API_URL = 'https://www.yammer.com/api/';
    /**
     *
     */
    const VERSION = 'v1';
    /**
     * @var
     */
    private $client_id;
    /**
     * @var
     */
    private $token;
    /**
     * @var \GuzzleHttp\Client
     */
    private $httpClient;
    /**
     * @var
     */
    private $response;
    /**
     * @var
     */
    private $content;
    /**
     * @var
     */
    private $body;

    /**
     * YammerClient constructor.
     */
    function __construct()
    {
        $this->client_id = config('yammer.client_id');
        $this->token = config('yammer.token');
        $this->httpClient = new \GuzzleHttp\Client();
    }

    /**
     * @param array $options
     *
     * @return $this
     */
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

    /**
     * @param null $groupId
     * @param array $options
     *
     * @return $this
     * @throws \Exception
     */
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

    /**
     * @param null $userId
     *
     * @return mixed
     */
    public function getUsers($userId = null)
    {
      $target = $this->_getFullUrl() . '/users';

      $target .= is_null($userId) ? '.json' : '/' . $userId . '.json';

      $response = $this->httpClient->request(
        'GET',
        $target, [
        'headers' => [
          'Authorization' => 'Bearer ' . $this->token
        ]
      ]);

      $response = $this->_jsonToArray($response->getBody());
      return $response;
    }

    /**
     * @param string $key
     * @param null $type
     *
     * @return array|mixed|null
     */
    public function getBody($key = '', $type = null)
    {
      $result = $dataset = $this->body[$key];

      if (!is_null($type)) {
          $result = $this->_search($type, 'type', $dataset);
      }

      return $result;
    }

    /**
     * @return $this
     */
    public function reverse()
    {
      $this->content = array_reverse($this->content);

      return $this;
    }

    /**
     * @return $this
     */
    public function withUsers()
    {
        $users = $this->getBody('references', 'user');

        $this->content = array_map(function($message) use ($users) {

            $message['user'] = $this->_search($message['sender_id'], 'id', $users, 'first');

            if ($message['liked_by']['count'] > 0) {
                foreach ($message['liked_by']['names'] as $likeKey => $name) {
                    $message['liked_by']['names'][$likeKey]['user'] = $this->_search($name['user_id'], 'id', $users, 'first');
                }
            }

            if (count($message['notified_user_ids']) > 0) {
                foreach ($message['notified_user_ids'] as $notifyKey => $notifiedUserId) {
                    $message['notified_user_ids'][$notifyKey] = $this->_search($notifiedUserId, 'id', $users, 'first');
                }
            }

            return $message;
        }, $this->content);

        return $this;
    }

    /**
     * @param string $search_key
     * @param string $search_value
     *
     * @return $this
     */
    public function where($search_key = '', $search_value = '')
    {
        $this->content = array_where($this->content, function($value, $key) use ($search_key, $search_value) {
          return $value[$search_key] == $search_value;
        });

        return $this;
    }

    /**
     * @return mixed
     */
    public function getStatusCode()
    {
        return $this->response->getStatusCode();
    }

    /**
     * @return $this
     */
    public function excludeReplies()
    {
        $this->content = array_where($this->content, function($value, $key) {
          return $value['replied_to_id'];
        });
        return $this;
    }

    /**
     * @return $this
     */
    public function onlyReplies()
    {
        $this->content = array_where($this->content, function($value, $key) {
          return !$value['replied_to_id'];
        });
        return $this;
    }

    /**
     * @return mixed
     */
    public function get()
    {
        return $this->content;
    }

    /**
     * @return string
     */
    private function _getFullUrl()
    {
        return self::API_URL.self::VERSION;
    }

    /**
     * @param $dataset
     *
     * @return mixed
     */
    private function _jsonToArray($dataset)
    {
        return json_decode($dataset, TRUE);
    }

    /**
     * @param string $find
     * @param string $column
     * @param array $dataset
     * @param string $option
     *
     * @return array|mixed|null
     */
    private function _search($find = '', $column = '', $dataset = [], $option = '')
    {
        $result = [];

        foreach ($dataset as $key => $data) {
            if ($data[$column] == $find) {
                array_push($result, $data);
            }
        }

        if(!empty($result)){
            if ($option == 'first') {
                return current($result);
            } else if ($option == 'last') {
                return end($result);
            } else {
                return $result;
            }
        } else {
            return null;
        }
    }
}
