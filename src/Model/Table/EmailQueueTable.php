<?php
namespace EmailQueue\Model\Table;

use Cake\Core\Configure;
use Cake\Database\Expression\QueryExpression;
use Cake\Database\Schema\TableSchema;
use Cake\Database\Type;
use Cake\I18n\FrozenTime;
use Cake\ORM\Table;
use EmailQueue\Database\Type\JsonType;
use EmailQueue\Database\Type\SerializeType;
use LengthException;

/**
 * EmailQueue Table.
 */
class EmailQueueTable extends Table
{
    const MAX_TEMPLATE_LENGTH = 100;

    /**
     * {@inheritdoc}
     */
    public function initialize(array $config = [])
    {
        Type::map('email_queue.json', JsonType::class);
        Type::map('email_queue.serialize', SerializeType::class);
        $this->addBehavior(
            'Timestamp',
            [
            'events' => [
                'Model.beforeSave' => [
                    'created' => 'new',
                    'modified' => 'always'
                ]
            ]
            ]
        );
    }

    /**
     * Stores a new email message in the queue.
     *
     * @param mixed $to      email or array of emails as recipients
     * @param array $data    associative array of variables to be passed to the email template
     * @param array $options list of options for email sending. Possible keys:
     *
     * - subject : Email's subject
     * - send_at : date time sting representing the time this email should be sent at (in UTC)
     * - template :  the name of the element to use as template for the email message
     * - layout : the name of the layout to be used to wrap email message
     * - format: Type of template to use (html, text or both)
     * - config : the name of the email config to be used for sending
     *
     * @throws \Exception any exception raised in transactional callback
     * @throws LengthException If `template` option length is greater than maximum allowed length
     * @return bool
     */
    public function enqueue($to, array $data, array $options = [])
    {
        if (strlen($options['template']) > self::MAX_TEMPLATE_LENGTH) {
            throw new LengthException('`template` length must be less or equal to ' . self::MAX_TEMPLATE_LENGTH);
        }

        $defaults = [
            'subject'       => '',
            'send_at'       => new FrozenTime('now'),
            'template'      => 'default',
            'layout'        => 'default',
            'theme'         => '',
            'format'        => 'both',
            'headers'       => [],
            'template_vars' => $data,
            'config'        => 'default',
            'attachments'   => []
        ];

        $email = $options + $defaults;
        if (!is_array($to)) {
            $to = [$to];
        }

        foreach (['cc', 'bcc'] as $k) {
            if (!empty($options[$k])) {
                if (!is_array($options[$k])) {
                    $options[$k] = [$options[$k]];
                }
                $email += ["email_{$k}" => $options[$k]];
            }
        }

        $emails = [];
        // foreach ($to as $t) {
            $emails[] = ['email_to' => $to] + $email;
        // }

        $emails = $this->newEntities($emails);

        return $this->getConnection()->transactional(function () use ($emails) {
            $failure = collection($emails)
                ->map(function ($email) {
                    return $this->save($email);
                })
                ->contains(false);

            return !$failure;
        });
    }

    /**
     * Returns a list of queued emails that needs to be sent.
     *
     * @param int $size number of unset emails to return
     * @throws \Exception any exception raised in transactional callback
     * @return array list of unsent emails
     */
    public function getBatch($size = 10)
    {
        return $this->getConnection()->transactional(function () use ($size) {
            $emails = $this->find()
                ->where([
                    $this->aliasField('sent') => false,
                    $this->aliasField('send_tries') . ' <=' => 3,
                    $this->aliasField('send_at') . ' <=' => new FrozenTime('now'),
                    $this->aliasField('locked') => false,
                ])
                ->limit($size)
                ->order([$this->aliasField('created') => 'ASC']);

            $emails
                ->extract('id')
                ->through(function (\Cake\Collection\CollectionInterface $ids) {
                    if (!$ids->isEmpty()) {
                        $this->updateAll(['locked' => true], ['id IN' => $ids->toList()]);
                    }

                    return $ids;
                });

            return $emails->toList();
        });
    }

    /**
     * Releases locks for all emails in $ids.
     *
     * @param array|Traversable $ids The email ids to unlock
     *
     * @return void
     */
    public function releaseLocks($ids)
    {
        $this->updateAll(['locked' => false], ['id IN' => $ids]);
    }

    /**
     * Releases locks for all emails in queue, useful for recovering from crashes.
     *
     * @return void
     */
    public function clearLocks()
    {
        $this->updateAll(['locked' => false], '1=1');
    }

    /**
     * Marks an email from the queue as sent.
     *
     * @param string $id queued email id
     * @return void
     */
    public function success($id)
    {
        $this->updateAll(['sent' => true], ['id' => $id]);
    }

    /**
     * Marks an email from the queue as failed, and increments the number of tries.
     *
     * @param string $id queued email id
     * @param string $error message
     * @return void
     */
    public function fail($id, $error = null)
    {
        $this->updateAll(
            [
                'send_tries'    => new QueryExpression('send_tries + 1'),
                'error'         => $error
            ],
            [
                'id' => $id
            ]
        );
    }

    /**
     * Sets the column type for template_vars and headers to json.
     *
     * @param TableSchema $schema The table description
     * @return TableSchema
     */
    protected function _initializeSchema(TableSchema $schema)
    {
        $type = Configure::read('EmailQueue.serialization_type') ?: 'email_queue.serialize';
        $schema->setColumnType('template_vars', $type);
        $schema->setColumnType('headers', $type);
        $schema->setColumnType('attachments', $type);
        $schema->setColumnType('email_to', $type);
        $schema->setColumnType('email_cc', $type);
        $schema->setColumnType('email_bcc',$type);

        return $schema;
    }
}
