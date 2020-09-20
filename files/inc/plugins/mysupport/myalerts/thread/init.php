<?php
/**
 * MySupport 1.8.0

 * Copyright 2010 Matthew Rogowski
 * https://matt.rogow.ski/

 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at

 ** http://www.apache.org/licenses/LICENSE-2.0

 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
**/

class MybbStuff_MyAlerts_Formatter_OUGCPrivateThreads_ThreadFormatter extends MybbStuff_MyAlerts_Formatter_AbstractFormatter
{
    public function formatAlert(MybbStuff_MyAlerts_Entity_Alert $alert, array $outputAlert)
    {
        global $parser, $lang;

        $tid = $alert->getObjectId();

        $thread = get_thread($tid);

        if(!($parser instanceof \postParser))
        {
            require_once MYBB_ROOT . 'inc/class_parser.php';
    
            $parser = new \postParser;
        }

        return $lang->sprintf(
            $lang->myalerts_mysupport_thread,
            $outputAlert['from_user'],
            htmlspecialchars_uni($parser->parse_badwords($thread['subject'])),
            $outputAlert['dateline']
        );
    }

    public function init()
    {
        \OUGCPrivateThreads\Core\load_language();    
    }

    public function buildShowLink(MybbStuff_MyAlerts_Entity_Alert $alert)
    {
        global $mybb;

        return $mybb->settings['bburl'] . '/' . get_thread_link(
            $alert->getObjectId()
        );
    }
}