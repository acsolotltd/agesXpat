<?php

declare(strict_types=1);

ob_start();

session_start();


use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;


/*
|--------------------------------------------------------------------------
| PHPMailer
|--------------------------------------------------------------------------
*/

require __DIR__ . '/vendor/autoload.php';


/*
|--------------------------------------------------------------------------
| Response Helpers
|--------------------------------------------------------------------------
*/

function response(
    int $status,
    string $html
): never {

    http_response_code($status);

    header(
        'Content-Type: text/html; charset=utf-8'
    );

    echo $html;

    exit;

}


/*
|--------------------------------------------------------------------------
| HTML Escape
|--------------------------------------------------------------------------
*/

function e(?string $value): string {

    return htmlspecialchars(
        trim($value ?? ''),
        ENT_QUOTES | ENT_SUBSTITUTE,
        'UTF-8'
    );

}


/*
|--------------------------------------------------------------------------
| Only POST
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    response(
        405,
        '
        <div class="rounded-xl border border-red-200
                    bg-red-50 dark:bg-red-950/30
                    dark:border-red-900 p-4
                    text-sm text-red-700 dark:text-red-300">

            Invalid request method.

        </div>
        '
    );

}


/*
|--------------------------------------------------------------------------
| Honeypot
|--------------------------------------------------------------------------
*/

if (
    !empty(
        $_POST['website'] ?? ''
    )
) {

    response(
        422,
        '
        <div class="rounded-xl border border-red-200
                    bg-red-50 dark:bg-red-950/30
                    dark:border-red-900 p-4
                    text-sm text-red-700 dark:text-red-300">

            Unable to process your request.

        </div>
        '
    );

}


/*
|--------------------------------------------------------------------------
| CSRF
|--------------------------------------------------------------------------
*/

$sessionToken =
    $_SESSION['csrf_token'] ?? '';

$formToken =
    $_POST['csrf_token'] ?? '';


if (
    empty($sessionToken) ||
    empty($formToken) ||
    !hash_equals(
        $sessionToken,
        $formToken
    )
) {

    response(
        403,
        '
        <div class="rounded-xl border border-red-200
                    bg-red-50 dark:bg-red-950/30
                    dark:border-red-900 p-4
                    text-sm text-red-700 dark:text-red-300">

            Your security session has expired.
            Please refresh the page and try again.

        </div>
        '
    );

}


/*
|--------------------------------------------------------------------------
| Collect Input
|--------------------------------------------------------------------------
*/

$inquiryType =
    trim($_POST['inquiry_type'] ?? '');

$fullName =
    trim($_POST['full_name'] ?? '');

$email =
    trim($_POST['email'] ?? '');

$phone =
    trim($_POST['phone'] ?? '');

$company =
    trim($_POST['company'] ?? '');

$timeline =
    trim($_POST['timeline'] ?? '');

$budget =
    trim($_POST['budget'] ?? '');

$message =
    trim($_POST['message'] ?? '');

$services =
    $_POST['services'] ?? [];


/*
|--------------------------------------------------------------------------
| Normalize Services
|--------------------------------------------------------------------------
*/

if (!is_array($services)) {

    $services = [];

}


$services =
    array_map(
        'trim',
        $services
    );


$services =
    array_filter(
        $services
    );


/*
|--------------------------------------------------------------------------
| Validation
|--------------------------------------------------------------------------
*/

$errors = [];


$allowedInquiryTypes = [
    'consult_expert',
    'request_audit',
    'project_implementation',
    'general_support'
];


if (
    !in_array(
        $inquiryType,
        $allowedInquiryTypes,
        true
    )
) {

    $errors[] =
        'Please select a valid inquiry type.';

}


if ($fullName === '') {

    $errors[] =
        'Please provide your full name.';

}


if (
    $email === '' ||
    !filter_var(
        $email,
        FILTER_VALIDATE_EMAIL
    )
) {

    $errors[] =
        'Please provide a valid email address.';

}


if ($phone === '') {

    $errors[] =
        'Please provide your phone number.';

}


if ($message === '') {

    $errors[] =
        'Please describe your requirements.';

}


if (
    mb_strlen($message) > 2000
) {

    $errors[] =
        'Your message must not exceed 2,000 characters.';

}


if (!empty($errors)) {

    $items = '';

    foreach ($errors as $error) {

        $items .=
            '<li>' . e($error) . '</li>';

    }


    response(
        422,
        '
        <div class="rounded-xl border border-red-200
                    bg-red-50 dark:bg-red-950/30
                    dark:border-red-900 p-5
                    text-sm text-red-700
                    dark:text-red-300">

            <p class="font-bold mb-2">
                Please correct the following:
            </p>

            <ul class="list-disc pl-5 space-y-1">
                ' . $items . '
            </ul>

        </div>
        '
    );

}


/*
|--------------------------------------------------------------------------
| File Upload
|--------------------------------------------------------------------------
*/

$attachmentPath = null;

$attachmentName = null;


if (
    isset($_FILES['attachment']) &&
    $_FILES['attachment']['error'] !==
        UPLOAD_ERR_NO_FILE
) {

    $file =
        $_FILES['attachment'];


    if (
        $file['error'] !==
        UPLOAD_ERR_OK
    ) {

        response(
            422,
            '
            <div class="rounded-xl border border-red-200
                        bg-red-50 dark:bg-red-950/30
                        dark:border-red-900 p-4
                        text-sm text-red-700
                        dark:text-red-300">

                The attachment could not be uploaded.

            </div>
            '
        );

    }


    /*
    |------------------------------------------------------------------
    | 10MB
    |------------------------------------------------------------------
    */

    $maxFileSize =
        10 * 1024 * 1024;


    if (
        $file['size'] >
        $maxFileSize
    ) {

        response(
            422,
            '
            <div class="rounded-xl border border-red-200
                        bg-red-50 dark:bg-red-950/30
                        dark:border-red-900 p-4
                        text-sm text-red-700
                        dark:text-red-300">

                Attachment must not exceed 10MB.

            </div>
            '
        );

    }


    /*
    |------------------------------------------------------------------
    | Validate extension
    |------------------------------------------------------------------
    */

    $originalName =
        $file['name'];


    $extension =
        strtolower(
            pathinfo(
                $originalName,
                PATHINFO_EXTENSION
            )
        );


    $allowedExtensions = [
        'pdf',
        'doc',
        'docx',
        'png',
        'jpg',
        'jpeg',
        'dwg',
        'dxf'
    ];


    if (
        !in_array(
            $extension,
            $allowedExtensions,
            true
        )
    ) {

        response(
            422,
            '
            <div class="rounded-xl border border-red-200
                        bg-red-50 dark:bg-red-950/30
                        dark:border-red-900 p-4
                        text-sm text-red-700
                        dark:text-red-300">

                This attachment type is not supported.

            </div>
            '
        );

    }


    /*
    |------------------------------------------------------------------
    | Temporary upload path
    |------------------------------------------------------------------
    */

    $attachmentPath =
        $file['tmp_name'];

    $attachmentName =
        basename($originalName);

}


/*
|--------------------------------------------------------------------------
| Inquiry Labels
|--------------------------------------------------------------------------
*/

$inquiryLabels = [

    'consult_expert' =>
        'Consult an Expert',

    'request_audit' =>
        'Request an Audit',

    'project_implementation' =>
        'Project Quote',

    'general_support' =>
        'General / Training'

];


$inquiryLabel =
    $inquiryLabels[$inquiryType]
    ?? $inquiryType;


/*
|--------------------------------------------------------------------------
| Services
|--------------------------------------------------------------------------
*/

$servicesText =
    !empty($services)
        ? implode(
            ', ',
            $services
        )
        : 'None specified';


/*
|--------------------------------------------------------------------------
| PHPMailer
|--------------------------------------------------------------------------
*/

$mail = new PHPMailer(true);


try {

    /*
    |--------------------------------------------------------------------------
    | SMTP
    |--------------------------------------------------------------------------
    */

    $mail->isSMTP();

    $mail->Host =
        'smtp.yourdomain.com';

    $mail->SMTPAuth =
        true;

    $mail->Username =
        'your-email@yourdomain.com';

    $mail->Password =
        'YOUR_SMTP_PASSWORD';

    $mail->SMTPSecure =
        PHPMailer::ENCRYPTION_STARTTLS;

    $mail->Port =
        587;


    /*
    |--------------------------------------------------------------------------
    | Sender
    |--------------------------------------------------------------------------
    */

    $mail->setFrom(
        'your-email@yourdomain.com',
        'Website Contact Form'
    );


    /*
    |--------------------------------------------------------------------------
    | Recipient
    |--------------------------------------------------------------------------
    */

    $mail->addAddress(
        'your-email@yourdomain.com',
        'Technical Team'
    );


    /*
    |--------------------------------------------------------------------------
    | Reply-To
    |--------------------------------------------------------------------------
    */

    $mail->addReplyTo(
        $email,
        $fullName
    );


    /*
    |--------------------------------------------------------------------------
    | Attachment
    |--------------------------------------------------------------------------
    */

    if (
        $attachmentPath !== null
    ) {

        $mail->addAttachment(
            $attachmentPath,
            $attachmentName
        );

    }


    /*
    |--------------------------------------------------------------------------
    | Email
    |--------------------------------------------------------------------------
    */

    $mail->isHTML(true);

    $mail->Subject =
        'New Website Inquiry: ' .
        $inquiryLabel;


    $mail->Body = '

        <h2>New Website Inquiry</h2>

        <hr>

        <h3>Inquiry Type</h3>

        <p>
            <strong>' .
            e($inquiryLabel) .
            '</strong>
        </p>


        <h3>Services</h3>

        <p>' .
            e($servicesText) .
        '</p>


        <h3>Contact Information</h3>

        <p>
            <strong>Name:</strong>
            ' . e($fullName) . '
        </p>

        <p>
            <strong>Email:</strong>
            ' . e($email) . '
        </p>

        <p>
            <strong>Phone:</strong>
            ' . e($phone) . '
        </p>

        <p>
            <strong>Company:</strong>
            ' . e($company ?: 'Not provided') . '
        </p>


        <h3>Project Information</h3>

        <p>
            <strong>Timeline:</strong>
            ' . e($timeline) . '
        </p>

        <p>
            <strong>Budget:</strong>
            ' . e($budget) . '
        </p>


        <h3>Message</h3>

        <p>' .
            nl2br(e($message)) .
        '</p>

    ';


    $mail->AltBody =
        "New Website Inquiry\n\n" .
        "Inquiry Type: {$inquiryLabel}\n" .
        "Services: {$servicesText}\n\n" .
        "Name: {$fullName}\n" .
        "Email: {$email}\n" .
        "Phone: {$phone}\n" .
        "Company: {$company}\n\n" .
        "Timeline: {$timeline}\n" .
        "Budget: {$budget}\n\n" .
        "Message:\n{$message}";


    /*
    |--------------------------------------------------------------------------
    | Send
    |--------------------------------------------------------------------------
    */

    $mail->send();


    /*
    |--------------------------------------------------------------------------
    | Rotate CSRF token
    |--------------------------------------------------------------------------
    */

    $_SESSION['csrf_token'] =
        bin2hex(random_bytes(32));


    /*
    |--------------------------------------------------------------------------
    | Success
    |--------------------------------------------------------------------------
    */

    response(
        200,
        '
        <div class="rounded-xl border border-emerald-200
                    bg-emerald-50 dark:bg-emerald-950/30
                    dark:border-emerald-900 p-5">

            <div class="flex items-start gap-3">

                <div class="flex-shrink-0">

                    <svg
                        class="w-6 h-6 text-emerald-600"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        stroke-width="2"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M5 13l4 4L19 7"
                        />
                    </svg>

                </div>


                <div>

                    <h3
                        class="font-bold text-emerald-800
                        dark:text-emerald-300"
                    >
                        Request Submitted Successfully
                    </h3>

                    <p
                        class="mt-1 text-sm text-emerald-700
                        dark:text-emerald-400"
                    >
                        Thank you for contacting us.
                        Our technical team will review
                        your request and get back to you
                        within 24 hours.
                    </p>

                </div>

            </div>

        </div>
        '
    );

}


catch (Exception $exception) {

    error_log(
        'Contact form error: ' .
        $exception->getMessage()
    );


    response(
        500,
        '
        <div class="rounded-xl border border-red-200
                    bg-red-50 dark:bg-red-950/30
                    dark:border-red-900 p-5">

            <div class="flex items-start gap-3">

                <svg
                    class="w-6 h-6 text-red-600 flex-shrink-0"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                    stroke-width="2"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"
                    />
                </svg>


                <div>

                    <h3
                        class="font-bold text-red-800
                        dark:text-red-300"
                    >
                        Unable to Send Request
                    </h3>

                    <p
                        class="mt-1 text-sm text-red-700
                        dark:text-red-400"
                    >
                        We could not process your request
                        right now. Please try again later.
                    </p>

                </div>

            </div>

        </div>
        '
    );

}
