<?php
namespace EmailQueue\Mailer;

use Cake\Mailer\Email as BaseEmail;

class Email extends BaseEmail {
    /**
     * Format addresses
     *
     * If the address contains non alphanumeric/whitespace characters, it will
     * be quoted as characters like `:` and `,` are known to cause issues
     * in address header fields.
     *
     * @param array $address Addresses to format.
     * @return array
     */
    protected function _formatAddress($address) {
        $return = [];
        foreach ($address as $email => $alias) {
            if ($email === $alias) {
                $return[] = $email;
            } else {
                $encoded = $this->_encode($alias);
                if ($encoded === $alias && preg_match('/[^a-z0-9 ]/i', $encoded)) {
                    $encoded = '"' . str_replace('"', '\"', $encoded) . '"';
                }
                $return[] = sprintf('"%s" <%s>', $encoded, $email);
            }
        }

        return $return;
    }
}