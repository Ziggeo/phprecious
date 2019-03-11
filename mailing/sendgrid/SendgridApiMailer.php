<?php

require_once(dirname(__file__) . "/../Mailer.php");

class SendgridApiMailer extends Mailer
{

    private $sendgrid;

    public function __construct($options = array()) {
        parent::__construct($options);

        if (@!$this->option("apikey"))
            throw new MailerException("Sendgrid Api Required.");

        $this->sendgrid = new SendGrid($this->option('apikey'));
    }

    protected function sendMail($mail) {
        $sendgridmail = new SendGrid\Mail\Mail();
        $sendgridmail->addTo($mail->recipient);
        $sendgridmail->setSubject($mail->subject);
        if(@$mail->message)
            $sendgridmail->addContent("text/plain", $mail->message);
        if(@$mail->message_html)
            $sendgridmail->addContent("text/html", $mail->message_html);
        $sender_arr = explode("<", $mail->sender);
        if (count($sender_arr) == 1)
            $sendgridmail->setFrom($mail->sender);
        else
            $sendgridmail->setFrom(trim(trim($sender_arr[1], ">")), trim($sender_arr[0]));
        if (@$mail->attachments) {
            foreach ($mail->attachments as $attachfile) {
                if (file_exists($attachfile)) {
                    $attachment = new SendGrid\Mail\Attachment();
                    $attachment->setContent(base64_encode(file_get_contents($attachfile)));
                    $attachment->setDisposition("attachment");
                    $attachment->setFilename(basename($attachfile));
                    $sendgridmail->addAttachment($attachment);
                }
            }
        }

        return $this->sendNow($sendgridmail);
    }

    private function sendNow($sendgridmail, $tries = 5) {
        $last_exception = NULL;
        while ($tries > 0) {
            try {
                return $this->sendgrid->send($sendgridmail);
            } catch (Exception $e) {
                $this->sendgrid = new SendGrid($this->option('apikey'));
                $tries--;
                $last_exception = $e;
            }
        }
        throw new MailerException($last_exception);
    }

}
