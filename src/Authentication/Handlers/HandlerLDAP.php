<?php

namespace CSUNMetaLab\Authentication\Handlers;

use Exception;

use Toyota\Component\Ldap\Core\Manager,
    Toyota\Component\Ldap\Platform\Native\Driver,
    Toyota\Component\Ldap\Exception\BindException;

use Toyota\Component\Ldap\API\ConnectionInterface;

/**
 * Handler class for LDAP operations using the Tiesa LDAP package.
 */
class HandlerLDAP
{
	private $ldap;

	// LDAP configuration
	private $host;
	private $basedn;
	private $dn;
	private $password;

	// values for searching
	private $search_user_id;
	private $search_username;
	private $search_mail;
	private $search_mail_array;

	// the LDAP query to be executed while searching for users
	private $search_auth_query;

	// true to allow auth binds without a password
	private $allowNoPass;

	// LDAP version to use
	private $version;

	/**
	 * Constructs a new HandlerLDAP object.
	 *
	 * @param string $host The LDAP hostname
	 * @param string $basedn The LDAP base DN
	 * @param string $dn The full LDAP DN for binding
	 * @param string $password The password for binding
	 * @param string $search_user_id The attribute to use for searching by user ID
	 * @param string $search_username The attribute to use for searching by username
	 * @param string $search_mail Optional attribute to use for searching by email
	 * @param string $search_mail_array Optional attribute to use for searching by email array
	 */
	public function __construct($host, $basedn, $dn, $password,
		$search_user_id, $search_username, $search_mail="mail",
		$search_mail_array="mailLocalAddress") {
		$this->host = $host;
		$this->basedn = $basedn;
		$this->dn = $dn;
		$this->password = $password;

		$this->search_user_id = $search_user_id;
		$this->search_username = $search_username;
		$this->search_mail = $search_mail;
		$this->search_mail_array = $search_mail_array;

		// false by default so we don't accidentally cause security problems
		$this->allowNoPass = false;

		// LDAPv3 by default
		$this->version = 3;

		// set the default auth search query
		$this->search_auth_query = "(|(" . $search_username . 
			"=%s)(" . $search_mail_array . "=%s))";
	}

	/**
	 * Attempts to bind to the LDAP server with the provided username and
	 * password. Throws a BindException if the bind operation fails.
	 *
	 * @param string $username The username with which to bind
	 * @param string $password The password with which to bind
	 * @throws BindException If the binding operation fails
	 */
	public function bind($username, $password) {
		$this->ldap->bind($username, $password);
	}

	/**
	 * Returns whether blank passwords are allowed for binding.
	 *
	 * @return boolean
	 */
	public function canAllowNoPass() {
		return $this->allowNoPass;
	}

	/**
	 * Connects and binds to the LDAP server. An optional username and password
	 * can be supplied to override the default credentials. Returns whether the
	 * connection and binding was successful.
	 *
	 * @param string $username The override username to use
	 * @param string $password The override password to use
	 *
	 * @throws Exception If the LDAP connection fails
	 * @return boolean
	 */
	public function connect($username="", $password="") {
		$params = array(
		    'hostname'  => $this->host,
		    'base_dn'   => $this->basedn,
		    'options' => [
		    	ConnectionInterface::OPT_PROTOCOL_VERSION => $this->version,
		    ],
		);
		$this->ldap = new Manager($params, new Driver());

		// connect to the server and bind with the credentials
		try
		{
			$this->ldap->connect();

			// if override parameters have been specified then use those
			// for the binding operation
			if(!empty($username)) {

				// bind by uid
				$selectedUsername = $this->search_username . "=" . $username . "," . $this->basedn;

				$selectedPassword = "";

				// do we allow empty passwords for bind attempts?
				if(empty($password)) {
					if($this->allowNoPass) {
						// yes so use the constructor-provided DN and password
						$selectedUsername = $this->dn;
						$selectedPassword = $this->password;
					}
				}
				else
				{
					// password provided so use what we were given
					$selectedPassword = $password;
				}

				// now perform the bind
				$this->bind($selectedUsername, $selectedPassword);
			}
			else
			{
				$this->bind($this->dn, $this->password);
			}

			// if it hits this return then the connection was successful and
			// the binding was also successful
			return true;
		}
		catch(BindException $be)
		{
			// could not bind with the provided credentials
			return false;
		}
		catch(Exception $e)
		{
			throw $e;
		}

		// something else went wrong
		return false;
	}

	/**
	 * Returns the value of the specified attribute from the result set. Returns
	 * null if the attribute could not be found.
	 *
	 * @param Result-instance $results The result-set to search through
	 * @param string $attr_name The attribute name to look for
	 * @return string|integer|boolean|null
	 */
    public function getAttributeFromResults($results, $attr_name) {
        foreach($results as $node) {
            foreach($node->getAttributes() as $attribute) {
                if (strtolower($attribute->getName()) == strtolower($attr_name)) {
                    return $attribute->getValues()[0]; // attribute found
                }
            }
        }
        return null;
    }

    /**
     * Returns whether the result set passed has at least one valid record in it.
     *
     * @param Result-instant $results The set of results to check
     * @return boolean
     */
    public function isValidResult($results) {
    	return $results->valid();
    }

    /**
	 * Queries LDAP for the record with the specified value for attributes
	 * matching what could commonly be used for authentication. For the
	 * purposes of this method, uid and mailLocalAddress are searched by
	 * default unless their values have been overridden.
	 *
	 * @param string $value The value to use for searching
	 * @return Result-instance
	 */
	public function searchByAuth($value) {
		// figure out how many times the placeholder occurs, then fill an
		// array that number of times with the search value
		$numArgs = substr_count($this->search_auth_query, "%s");
		$args = array_fill(0, $numArgs, $value);

		// format the string and then perform the search
		$searchStr = vsprintf($this->search_auth_query, $args);
		$results = $this->ldap->search($this->basedn, $searchStr);
		return $results;
	}

	/**
	 * Queries LDAP for the record with the specified email.
	 *
	 * @param string $email The email to use for searching
	 * @return Result-instance
	 */
	public function searchByEmail($email) {
		$results = $this->ldap->search($this->basedn,
			$this->search_mail . '=' . $email);
		return $results;
	}

	/**
	 * Queries LDAP for the record with the specified mailLocalAddress.
	 *
	 * @param string $email The mailLocalAddress to use for searching
	 * @return Result-instance
	 */
	public function searchByEmailArray($email) {
		$results = $this->ldap->search($this->basedn,
			$this->search_mail_array . '=' . $email);
		return $results;
	}

	/**
	 * Queries LDAP for the records using the specified query.
	 *
	 * @param string $query Any valid LDAP query to use for searching
	 * @return Result-instance
	 */
	public function searchByQuery($query) {
		$results = $this->ldap->search($this->basedn, $query);
		return $results;
	}

	/**
	 * Queries LDAP for the record with the specified uid.
	 *
	 * @param string $uid The uid to use for searching
	 * @return Result-instance
	 */
	public function searchByUid($uid) {
		$results = $this->ldap->search($this->basedn,
			$this->search_username . '=' . $uid);
		return $results;
	}

	/**
	 * Sets whether blank passwords are allowed for binding attempts.
	 *
	 * @param boolean $allowNoPass Whether to allow blank passwords
	 */
	public function setAllowNoPass($allowNoPass) {
		$this->allowNoPass = $allowNoPass;
	}

	/**
	 * Sets the query used within the searchByAuth() method. This should be
	 * structured in a vsprintf()-compatible format and use %s as the
	 * placeholder for the search value.
	 *
	 * @param string $search_auth_query LDAP query to use
	 */
	public function setAuthQuery($search_auth_query) {
		$this->search_auth_query = $search_auth_query;
	}

	/**
	 * Sets the base DN used during queries.
	 *
	 * @param string $basedn The base DN to use
	 */
	public function setBaseDN($basedn) {
		$this->basedn = $basedn;
	}

	/**
	 * Sets the LDAP version to be used.
	 *
	 * @param int $version The LDAP version to use
	 */
	public function setVersion($version) {
		$this->version = $version;
	}
}
