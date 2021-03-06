AddIdentity Method
==================

Create or update an identity and associate it with an existing user account.

User accounts can have multiple identities (login methods) associated with them.

Request Format
--------------

+-----------------+----------------------------------+--------+------------+
| *Parameter*     | *Description*                    | *Type* | *Required* |
+=================+==================================+========+============+
| `RequestMethod` | AddIdentity                      | String | Yes        |
+-----------------+----------------------------------+--------+------------+
| `Identifier`    | Identifier, for example a login  | String | Yes        |
|                 | name                             |        |            |
+-----------------+----------------------------------+--------+------------+
| `Credential`    | Credential, for example a hashed | String | Yes        |
|                 | password                         |        |            |
+-----------------+----------------------------------+--------+------------+
| `Type`          | Identity type, such as "md5hash" | String | Yes        |
+-----------------+----------------------------------+--------+------------+
| `UserID`        | UUID of the user to associate    | UUID   | Yes        |
|                 | the identity with                |        |            |
+-----------------+----------------------------------+--------+------------+

Sample request: ::

    RequestMethod=AddIdentity
    &Identifier=loginname
    &Credential=%241%2479e01ab95f5df3ddf654224935644ff1
    &Type=md5hash
    &UserID=d2672f4f-71d2-41cf-9470-911ef941d177


Response Format
---------------

+-------------+---------------------------------------------+---------+
| *Parameter* | *Description*                               | *Type*  | 
+=============+=============================================+=========+
| `Success`   | True if an identity was created or updated, | Boolean |
|             | False if a Message was returned             |         | 
+-------------+---------------------------------------------+---------+
| `Message`   | Error message                               | String  | 
+-------------+---------------------------------------------+---------+

Success: ::


    {
        "Success":true
    }


Failure: ::


    {
        "Success":false,
        "Message":"User account not found"
    }

