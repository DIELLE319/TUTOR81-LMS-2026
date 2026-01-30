<?php
/* Change all key to uniqid(sha1('site_url'))*/
class APIKey
{
    private $APIkeys = array(
        'tutor81.com'       => '77de3124a2fac942cc012a15647d1ff8e5a69be8',
        'www.tutor81.com'   => '69f1c204aeeaba7bb6a3cd9b60fbfed1dbd7d073',
        'tutor81.it'        => '41d0517a20a63b4b4d62768172958b2d4cf701af55f92f54a716a',
        'www.tutor81.it'    => '41d0517a20a63b4b4d62768172958b2d4cf701af55f92f54a716a',
	'localhost'         => '41d0517a20a63b4b4d62768172958b2d4cf701af55f92f54a716a',
        '57.131.39.140'     => '36691df2f0685e2e4e613108e4fc82cbdb0a148d6975411d3087c'
    );
    
    public function verifyKey($key, $origin){
        //return key_exists($key, $this->APIkeys) && $this->APIkeys[$key] == $origin;
        return key_exists($origin, $this->APIkeys) && $this->APIkeys[$origin] == $key;
    }
}