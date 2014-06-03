# Init script for WordPress trunk site

echo "Commencing Theater for WordPress Setup"

# Make a database, if we don't already have one
echo "Creating database (if it's not already there)"
mysql -u root --password=root -e "CREATE DATABASE IF NOT EXISTS wpt_test"
mysql -u root --password=root -e "GRANT ALL PRIVILEGES ON wpt_test.* TO wp@localhost IDENTIFIED BY 'wp';"

# Check for the presence of a `htdocs` folder.
if [ ! -d htdocs ]
then
    echo "Checking out WordPress SVN"
    # If `htdocs` folder doesn't exist, check out WordPress
    # as that folder
    svn checkout http://svn.automattic.com/wordpress/trunk/ htdocs
    # Change into the `htdocs` folder we've checked SVN out into
    cd htdocs
    
    # Use WP CLI to create a `wp-config.php` file
    wp core config --dbname="wpt_test" --dbuser=wp --dbpass=wp --dbhost="localhost" --allow-root
    # Use WP CLI to install WordPress
    wp core install --url=theater.dev --title="Theater for WordPress" --admin_user=admin --admin_password=password --admin_email=jeroen@slimndap.com --allow-root

	echo "Installing Theater for WordPress plugin"
	git clone https://github.com/slimndap/wp-theatre.git wp-content/plugins/theater

	echo "Generate the plugin test files"
	wp scaffold plugin-tests theater --allow-root

	# echo "Initialize the testing environment locally"
	# cd $(wp plugin path --dir --allow-root theater)
	# bash bin/install-wp-tests.sh wordpress_test wp 'wp' localhost latest

    # Change folder to the parent folder of `htdocs`
    cd ..
else
    echo "Updating WordPress SVN"
    # If the `htdocs` folder exists, then run SVN update
    svn up htdocs
fi


# The Vagrant site setup script will restart Nginx for us

# Let the user know the good news
echo "Theater for WordPress installed";