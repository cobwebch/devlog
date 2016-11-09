.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../Includes.txt


.. _developer:

Developer manual
----------------


.. _developer-logging-method:

Using the logging method
^^^^^^^^^^^^^^^^^^^^^^^^

To log something simply call :code:`\TYPO3\CMS\Core\Utility\GeneralUtility::devLog()`.
This method takes the following parameters:

+-----------+-----------------------------------------------------------------------+
| $msg      | The message that you want to write to the log                         |
+-----------+-----------------------------------------------------------------------+
| $extKey   | The key of the extension writing to the log                           |
+-----------+-----------------------------------------------------------------------+
| $severity | Indication of the severity of the message. The following values are   |
|           | expected:                                                             |
|           |                                                                       |
|           | - -1 for ok status                                                    |
|           | - 0 for a purely informational message                                |
|           | - 1 for a notice                                                      |
|           | - 2 for a warning                                                     |
|           | - 3 for a (fatal) error                                               |
|           |                                                                       |
|           | This parameter is optional and defaults to 0.                         |
+-----------+-----------------------------------------------------------------------+
| $dataVar  | This variable can be any type of data that you wish and that you find |
|           | useful for information or debugging purposes. The Database Writer     |
|           | serializes it before storage in the database.                         |
|           |                                                                       |
|           | This parameter is optional and defaults to false.                     |
+-----------+-----------------------------------------------------------------------+

.. warning::

   If you store a lot of stuff in the :code:`$dataVar` or
   if you call the "devLog" very frequently, you may end up with a
   very large "tx\_devlog\_domain\_model|_entry" table in your database.
   Check it out regularly and don't hesitate to use the :ref:`clean up features <user-backend-module-clear-entries>`
   as the relevant :ref:`configuration options <installation-configuration-database-writer>`.


.. _developer-logging-method-severity-levels:

More about severity levels
""""""""""""""""""""""""""

It may not always be easy to choose a severity level. The descriptions
below go into a bit more detail and will – hopefully – make the choice
easier.

OK (-1)
  These events indicate that everything went fine, no
  error occurred (at least up to that point where the event was
  created). No action needs to be taken.

Info (0)
  These events are purely informational. They are
  normally used for debugging purposes only and require no special
  action.

Notice (1)
  Abnormal condition, but not blocking. Notices are
  meant to raise attention. Processes have been completed, but things
  are not running as smoothly as they could and the condition should be
  investigated.

Warning (2)
  These events are used to notify significant
  problems. Processes have been completed, but parts of them may be
  missing, wrong or corrupted. Warnings should not be ignored and action
  should definitely be taken.

Error (3)
  These events signal that something went fatally wrong.
  Processes were not completed and action is definitely needed.
  Alternately this level may be used to point to a failed event, but in
  a process where failure can be expected, e.g. a login attempt with the
  wrong password.


.. _developer-extra-information:

Extra information
^^^^^^^^^^^^^^^^^

The devlog extension stores information beyond the parameters passed to the
:code:`\TYPO3\CMS\Core\Utility\GeneralUtility::devLog()` method. Some of it
is automatically retrieved, like the id of the currently logged in BE user, if any.

If the devlog call is made in the FE context, the page id will also automatically
be retrieved from :code:`$GLOBALS['TSFE']->id. This variable is not defined in other contexts,
in particular the BE. There is still a way to pass a page id to the devlog, if it makes sense,
but you need to set it yourself in the global variable
:code:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['GLOBAL']['debugData']['pid']`.
If defined, this variable will be taken into account by the devlog.

All this work happens in the :class:`\\Devlog\\Devlog\\Utility\\Logger` class.


.. _developer-custom-writers:

Custom Writers
^^^^^^^^^^^^^^

The data passed in the :code:`\TYPO3\CMS\Core\Utility\GeneralUtility::devLog()`
call is stored by classed called "Writers". The extension provides two Writers
out of the box: one write to the database, the other to a file. It is pretty
easy to develop custom Writers.

First you want to create a new class which extends
:class:`\\Devlog\\Devlog\\Writer\\AbstractWriter`, which itself implements
:class:`\\Devlog\\Devlog\\Writer\\WriterInterface`. The interface
defines a single method called :code:`write()` which should take care of
storing each log entry wherever and however it wants.

Once you hace created your writer, register it in your extension's
:file:`ext_localconf.php` file with the following syntax:

.. code-block:: php

	$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['devlog']['writers']['foobar'] = \Foo\Bar\Writer\FooBarWriter::class;


where :code:`foobar` should be a unique key for your Writer.

.. note::

   It is actually possible to overwrite the default Writers be registrer a different
   class for keys :code:`db` (Database Writer) and :code:`file` (File Writer).

   It would also be possible to disable a Writer, by unsetting its entry in the
   :code:`$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['devlog']['writers']` array.
