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

    public function setAttachments($attachments) {
        foreach ((array)$attachments as $name => $fileInfo) {
            if (isset($fileInfo['s3url'])) {
                if (filter_var($fileInfo['s3url'], FILTER_VALIDATE_URL) !== false) {
                    $localpath = TMP . pathinfo(parse_url($fileInfo['s3url'], PHP_URL_PATH), PATHINFO_BASENAME);
                    file_put_contents($localpath, fopen($fileInfo['s3url'], 'r'));
                    if (file_exists($localpath)) {
                        $attachments[$name]['file'] = $localpath;
                    } else {
                        unset($attachments[$name]);
                    }
                } else {
                    unset($attachments[$name]);
                }
            }
        }
        
        return parent::setAttachments($attachments);
    }
}