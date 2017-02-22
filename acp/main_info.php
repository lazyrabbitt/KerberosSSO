<?php

namespace LazyMod\KerberosSSO\acp;

class main_info
{
    public function module()
    {
        return array(
            'filename'  => '\LazyMod\KerberosSSO\acp\main_module',
            'title'     => 'Kerberos SSO Additional Functions',
            'modes'    => array(
                'settings'  => array(
                    'title' => 'Update all Users',
                    'auth'  => 'acl_a_board',
                    'cat'   => array('Kerberos SSO Additional Functions'),
                ),
            ),
        );
    }
}