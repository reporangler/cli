# Repo Rangler Command Line Interface
A command line tool to interact with the services and provides a consistent interface to dealing with the service and 
all it's functionalities that can be used through the command line. 

Most commands will self-correct you if you attempt to use plurals. For example `list-package-groups` will be 
corrected to `list-package-group`. The commands are singular unless explicitly stated otherwise

## Supported functionality

#### User Commands
- login
    - This command will login and update the state stored on disk with the user_id and login_token
    - It will ask for the `username` and `password`
- list-user
    - This command will list all the registered users in the system
- create-user
    - This will ask you the information to create a new user
    - It will ask for `username`, `email`, and `password`
- delete-user
    - This command will remove a user from the system
    - BE CAREFUL: This command won't ask for confirmation, nor do any extensive checks, it blindly executes your command
    - It requires to be equal to the username you want to delete
    - Example: `delete-user=username` 
- user-info
    - This command will show the user info

#### Package Group Commands
- list-package-group
    - This will list all the registered package groups
- create-package-group
    - This will create a new package group
    - It requires to be equal to the package group you want to create
    - Example: `create-package-group=name`
- delete-package-group
    - This will delete a package group
    - BE CAREFUL: This command won't ask for confirmation, nor do any extensive checks, it blindly executes your command
    - It requires to be equal to the package group you want to delete
    - Example: `delete-package-group=name`
    
#### Access Token Commands
- add-access-token
    - This will add an external systems access token
    - It's up to the repositories how to use them, this is just a storage mechanism, in a generic fashion
    - The access tokens are automatically returned as part of the user object
    - It accepts the following parameters: type, token
    - Example: `add-access-token --type=github --token=xxxxxxxx`
- list-access-token
    - This will list the access tokens for a particular user
- remove-access-token
    - This will delete an access token from the system
    - BE CAREFUL: This command won't ask for confirmation, nor do any extensive checks, it blindly executes your command
    - It will present you with a list of tokens to delete
    
#### Publishing Commands 
- publish
    - This will publish a new package through one of the supported/configured repository services
    - You need to provide the type, which is how the repository service is determined
    - The group is the package group you want to publish into
    - The functionality is limited, but you can say the url of the VCS repository to publish from
    - You can override the scanning mechanism that maybe the repository service uses, but this information might not 
        be useful to the repository service, it's only provided as part of the payload and if it's useful then it's available
    - Example: `publish --type=php --group=public --url=https://github.com/reporangler/lib-reporangler`

#### Missing Commands (but that exist in the Rest Api)
- Assign user into package group
- Remove user from package group
- Update published package
- Remove published package
