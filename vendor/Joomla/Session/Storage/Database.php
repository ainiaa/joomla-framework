<?php
/**
 * @copyright  Copyright (C) 2005 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Joomla\Session\Storage;

use Joomla\Session\Storage;
use Joomla\Factory;
use Exception;

/**
 * Database session storage handler for PHP
 *
 * @see         http://www.php.net/manual/en/function.session-set-save-handler.php
 * @since    1.0
 */
class Database extends Storage
{
	/**
	 * Read the data for a particular session identifier from the SessionHandler backend.
	 *
	 * @param   string  $id  The session identifier.
	 *
	 * @return  string  The session data.
	 *
	 * @since   1.0
	 */
	public function read($id)
	{
		// Get the database connection object and verify that it is connected.
		$db = Factory::getDbo();

		try
		{
			// Get the session data from the database table.
			$query = $db->getQuery(true);
			$query->select($db->quoteName('data'))
			->from($db->quoteName('#__session'))
			->where($db->quoteName('session_id') . ' = ' . $db->quote($id));

			$db->setQuery($query);

			return (string) $db->loadResult();
		}
		catch (Exception $e)
		{
			return false;
		}
	}

	/**
	 * Write session data to the SessionHandler backend.
	 *
	 * @param   string  $id    The session identifier.
	 * @param   string  $data  The session data.
	 *
	 * @return  boolean  True on success, false otherwise.
	 *
	 * @since   1.0
	 */
	public function write($id, $data)
	{
		// Get the database connection object and verify that it is connected.
		$db = Factory::getDbo();

		try
		{
			$query = $db->getQuery(true);
			$query->update($db->quoteName('#__session'))
			->set($db->quoteName('data') . ' = ' . $db->quote($data))
			->set($db->quoteName('time') . ' = ' . $db->quote((int) time()))
			->where($db->quoteName('session_id') . ' = ' . $db->quote($id));

			// Try to update the session data in the database table.
			$db->setQuery($query);

			if (!$db->execute())
			{
				return false;
			}

			// Since $db->execute did not throw an exception the query was successful.
			// Either the data changed, or the data was identical. In either case we are done.

			return true;
		}
		catch (Exception $e)
		{
			return false;
		}
	}

	/**
	 * Destroy the data for a particular session identifier in the SessionHandler backend.
	 *
	 * @param   string  $id  The session identifier.
	 *
	 * @return  boolean  True on success, false otherwise.
	 *
	 * @since   1.0
	 */
	public function destroy($id)
	{
		// Get the database connection object and verify that it is connected.
		$db = Factory::getDbo();

		try
		{
			$query = $db->getQuery(true);
			$query->delete($db->quoteName('#__session'))
			->where($db->quoteName('session_id') . ' = ' . $db->quote($id));

			// Remove a session from the database.
			$db->setQuery($query);

			return (boolean) $db->execute();
		}
		catch (Exception $e)
		{
			return false;
		}
	}

	/**
	 * Garbage collect stale sessions from the SessionHandler backend.
	 *
	 * @param   integer  $lifetime  The maximum age of a session.
	 *
	 * @return  boolean  True on success, false otherwise.
	 *
	 * @since   1.0
	 */
	public function gc($lifetime = 1440)
	{
		// Get the database connection object and verify that it is connected.
		$db = Factory::getDbo();

		// Determine the timestamp threshold with which to purge old sessions.
		$past = time() - $lifetime;

		try
		{
			$query = $db->getQuery(true);
			$query->delete($db->quoteName('#__session'))
			->where($db->quoteName('time') . ' < ' . $db->quote((int) $past));

			// Remove expired sessions from the database.
			$db->setQuery($query);

			return (boolean) $db->execute();
		}
		catch (Exception $e)
		{
			return false;
		}
	}
}
