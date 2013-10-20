<?php

// Method::create('test', function($data){
//     Logger::log($data);
//     return $data;
// });

// Method::create('sendEmail', function($data){
//     return Email::send($data['to'], 'email/test', $data);
// });

// Method::create('sendInvitationEmail', function($data = array()){
//     $user = Users::filterOne('email', $data['to']);
//     if($user) {
//         $data['subject'] = 'A project is shared with you.';
//         return Email::send($data['to'], 'email/shared', $data);
//     } else {
//         $data['subject'] = 'You\'ve been invited to tasks.hasrimy.com';
//         return Email::send($data['to'], 'email/invitation', $data);
//     }
// });

// Method::create('stateChange', function($data = array()){
//     if($data['isDone']) {
//         $data['subject'] = '"'.$data['taskName'].'" marked done.';
//         return Email::send($data['to'], 'email/stateChangeDone', $data);
//     } else {
//         $data['subject'] = 'State change for "'.$data['taskName'].'"';
//         return Email::send($data['to'], 'email/stateChange', $data);
//     }
// });
