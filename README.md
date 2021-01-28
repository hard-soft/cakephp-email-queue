# CakePHP Email Queue plugin #

This plugin provides an interface for creating emails on the fly and
store them in a queue to be processed later by an offline worker using a
cakephp shell command.

It also contains a handy shell for previewing queued emails, a very handy tool for modifying
email templates and watching the result.

### Enable plugin

Based on https://github.com/lorenzo/cakephp-email-queue/tree/3.3.1

## Requirements ##

* CakePHP 3.7
* (CakePHP 3.9 compatible)

## Installation ##

```sh
composer require lorenzo/cakephp-email-queue
```

The plugin uses Debug email transport, so make sure your email config contain it:

```
'EmailTransport' => [
        'Debug' => [
            'className' => 'Debug'
        ],
]
```

### Enable plugin

```sh
bin/cake plugin load EmailQueue
```

### Load required database table

In order to use this plugin, you need to create a database table.
Required SQL is located at

	# config/Schema/email_queue.sql

Just load it into your database. You are free to change the file to use an integer primary
key instead of UUIDs.

Or run migrations command:

    bin/cake migrations migrate --plugin EmailQueue

## Usage

Whenever you need to send an email, use the EmailQueue model to create
and queue a new one by storing the correct data:

    use EmailQueue\EmailQueue;
    EmailQueue::enqueue($to, $data, $options);

`EmailQueue::enqueue` method receives 3 arguments:

- First argument is a string or array of email addresses that will be treated as recipients or `to` param if component-method is used.
- Second arguments is an array of view variables to be passed to the
  email template, or `template_vars` array param if component-method is used.
  
`EmailComponent::enqueue` method can be used like `EmailQueue::enqueue` or by passing a single array with all key/values

    use EmailQueue\Controller\Component\EmailComponent;
    EmailComponent::enqueue($mail, [$data, $options]);

- Third arguments is an array of `options`, possible options are

 * `to`: String or Array with recipient(s) (component-method-only otherwise first param)
 * `template_vars`: Array with view variables (component-method-only otherwise second param)
 * `subject`: Email's subject
 * `send_at`: date time sting representing the time this email should be sent at (in UTC)
 * `template`:  the name of the element to use as template for the email message. (maximum supported length is 100 chars)
 * `layout`: the name of the layout to be used to wrap email message
 * `format`: Type of template to use (html, text or both)
 * `headers`: A key-value list of headers to send in the email
 * `theme`: The View Theme to find the email templates
 * `config`: the name of the email config to be used for sending
 * `from_name`: String with from name. Must be supplied together with `from_email`.
 * `from_email`: String with from email. Must be supplied together with `from_name`.
 * `cc`: String or array with email addresses
 * `bcc`: String or array with email addresses
 * `reply_to`: String with replyTo email address
 * `attachments` 
 ```json
[
    "[FILENAME]" => [
        "s3url" => "[PRESIGNED-S3-URL]",
    ],
    "[ANOTHER_FILENAME]" => [
        "file" => "[FILE-ON-FILESYSTEM]",
    ]
]
```

## Component-Examples

1. passed by three params

 ```json
$to = [
    "test@test.at",
    "test2@test.com"
];
$data = [
    "date" => "2021-01-01",
    "time" => "12:30:00",
    "message" => "This is a testmessage"
];
$options = [
    "template" => "confirmation",
    "format" => "html",
    "attachments" => [
        "myfile.txt" => [
            "s3url" => "https://dummyurl.test/myfile.txt"
        ],
        "test.png" => [
            "file" => "/tmp/test.png"
        ]
    ]
];

```

    use EmailQueue\Controller\Component\EmailComponent;
    EmailComponent::enqueue($to, $data, $options);

2. passed by array

 ```json
$mail = [
    "to" => [
        "test@test.at",
        "test2@test.com"
    ],
    "template_vars" => [
        "date" => "2021-01-01",
        "time" => "12:30:00",
        "message" => "This is a testmessage"
    ],
    "template" => "confirmation",
    "format" => "html",
    "attachments" => [
        "myfile.txt" => [
            "s3url" => "https://dummyurl.test/myfile.txt"
        ],
        "test.png" => [
            "file" => "/tmp/test.png"
        ]
    ]
];

```


    use EmailQueue\Controller\Component\EmailComponent;
    EmailComponent::enqueue($mail);


### Previewing emails

It is possible to preview emails that are still in the queue, this is very handy during development to check if the rendered
email looks at it should; no need to queue the email again, just make the changes to the template and run the preview again:

	# bin/cake EmailQueue.preview

### Sending emails

Emails should be sent using bundled Sender command, use `-h` modifier to
read available options

	# bin/cake EmailQueue.sender -h

You can configure this command to be run under a cron or any other tool
you wish to use.