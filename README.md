# Repo Rangler Command Line Interface
A command line tool to interact with the services and provides a consistent interface to dealing with the service and 
all it's functionalities that can be used through the command line. 

## Supported functionality

- login
    - This command will request your RepoRangler credentials and obtain a login token in order to perform other requests
- list-user
    - This command will list all the registered users in the system
- create-user
    - This will ask you the information to create a new user
- delete-user
    - This command requires a parameter `delete-user=username` is how you tell the system which user to delete 
- list-package-group
    - This will list all the registered package groups
- create-package-group
    - This command requires a parameter `create-package-group=name` to create a new group in the system
- delete-package-group
    - This command rqeuires a parameter `delete-package-group=name` to delete a group from the system