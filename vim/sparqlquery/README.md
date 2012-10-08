SPARQL queries from vim
=======================

Summary
-------

Adds a command to vim that enables querying of a SPARQL endpoint.

Gets the query from the content of the main vim window, then outputs the
results to a new split window.

Requirements
------------

* vim
* curl

Usage
-----

Copy the contents of sparqlquery.vim to your ~/.vimrc to make the command
available.

Enter a SPARQL query into the main vim window, and then run:

    :Sparql <endpoint>

subsequent calls to the same endpoint can be made with:

    :Sparql

Results from the SPARQL query will be placed into a new vim window.

Contact
-------

Suggestions, bugs etc. to:

* Dave Challis <suicas@gmail.com>
