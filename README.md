
# Laravel Blog Api

This is a blog api system written with laravel and Sanctum token base auth, that you can have multiple admins to write and manage your blog.
This project using TDD (test driven development) testing.
## Features

- Post 
    - create - update - delete
    - categories
    - tags
    - comments
    - likeable
- Category
    - create - update - delete
    - sub categories
- Tag
    - create - update - delete
- Comment
    - create - update - delete
    - replies
    - likeable
- Upload
    - upload images for Post
    - upload image for user profile
- Auth (Sanctum token base auth)
    - Register
    - Login / Logout
    - Forget password
- User
    - Posts
    - Comments
    - Liked posts
    - Promote to admin
    - Demote to normal user
    - Admin list
- Profile
    - Change name
    - Change password
    - Change profile image
## API Reference
###  Post
#### - Get all posts

```http
  GET /api/post
```

#### - Get single post

```http
  GET /api/post/{id}
```

| Parameter | Type     | Description                       |
| :-------- | :------- | :-------------------------------- |
| `id`      | `integer` | **Required**. id of post to fetch |

#### - Get  post comments

```http
  GET /api/post/{id}/comments
```

| Parameter | Type     | Description                       |
| :-------- | :------- | :-------------------------------- |
| `id`      | `integer` | **Required**. id of post to fetch comments |

#### - Like post

```http
  POST /api/post/{id}/like
```

| Parameter | Type     | Description                       |
| :-------- | :------- | :-------------------------------- |
| `id`      | `integer` | **Required**. id of post to like |

#### - Unlike post

```http
  POST /api/post/{id}/unlike
```

| Parameter | Type     | Description                       |
| :-------- | :------- | :-------------------------------- |
| `id`      | `integer` | **Required**. id of post to unlike |

#### - Store post

```http
  POST /api/post/
```

| Parameter | Type     | Description                       |
| :-------- | :------- | :-------------------------------- |
| `title`   | `string`| **Required**. title of post |
| `description`| `string`| **Required**. description of post |
| `categories`      | `integer array`| **Required**. categories id  |
| `tags`      | `integer array`| **Required**. tags id |

#### - Update post

```http
  PUT|PATCH /api/post/{id}
```

| Parameter | Type     | Description                       |
| :-------- | :------- | :-------------------------------- |
| `id`      | `integer` | **Required**. Id of post to update |
| `title`   | `string`| **Required**. title of post |
| `description`| `string`| **Required**. description of post |
| `categories`      | `integer array`| **Required**. categories id  |
| `tags`      | `integer array`| **Required**. tags id |

#### - Destroy post

```http
  DELETE /api/post/{id}
```

| Parameter | Type     | Description                       |
| :-------- | :------- | :-------------------------------- |
| `id`      | `integer` | **Required**. Id of post to destroy |

###  Category
#### - Get all categories

```http
  GET /api/category
```
#### - Get cateogry posts

```http
  GET /api/category/{id}/posts
```

| Parameter | Type     | Description                       |
| :-------- | :------- | :-------------------------------- |
| `id`      | `integer` | **Required**. id of category to get its posts |

#### - Store category

```http
  POST /api/category
```

| Parameter | Type     | Description                       |
| :-------- | :------- | :-------------------------------- |
| `name`   | `string`| **Required**. name of category |
| `parent_id`| `integer`| **optional**. parent id of category (to be sub category) |

#### - Update category

```http
  PUT|PATCH /api/category/{id}
```

| Parameter | Type     | Description                       |
| :-------- | :------- | :-------------------------------- |
| `id`      | `integer` | **Required**. id of category to update |
| `name`   | `string`| **Required**. name of category |
| `parent_id`| `integer`| **optional**. parent id of category (to be sub category) |

#### - Destroy category

```http
  DELETE /api/category/{id}
```

| Parameter | Type     | Description                       |
| :-------- | :------- | :-------------------------------- |
| `id`      | `integer` | **Required**. id of category to destroy |

###  Tag
#### - Get all tags

```http
  GET /api/tag
```
#### - Get tag posts

```http
  GET /api/tag/{id}/posts
```

| Parameter | Type     | Description                       |
| :-------- | :------- | :-------------------------------- |
| `id`      | `integer` | **Required**. id of tag to get its posts |

#### - Store tag

```http
  POST /api/tag
```

| Parameter | Type     | Description                       |
| :-------- | :------- | :-------------------------------- |
| `name`   | `string`| **Required**. name of tag |

#### - Update tag

```http
  PUT|PATCH /api/tag/{id}
```

| Parameter | Type     | Description                       |
| :-------- | :------- | :-------------------------------- |
| `id`      | `integer` | **Required**. id of tag to update |
| `name`   | `string`| **Required**. name of tag |

#### - Destroy tag

```http
  DELETE /api/tag/{id}
```

| Parameter | Type     | Description                       |
| :-------- | :------- | :-------------------------------- |
| `id`      | `integer` | **Required**. id of tag to destroy |

###  Comment
#### - Get all comments

```http
  GET /api/comment
```
#### - Store comment

```http
  POST /api/comment
```

| Parameter | Type     | Description                       |
| :-------- | :------- | :-------------------------------- |
| `post_id`   | `integer`| **Required**. id of post |
| `text`   | `string`| **Required**. text of comment |
| `parent_id`   | `integer`| **optional**. id of comment, if it's a reply |

#### - Update comment

```http
  PUT|PATCH /api/comment/{id}
```
| Parameter | Type     | Description                       |
| :-------- | :------- | :-------------------------------- |
| `id`   | `integer`| **Required**. id of comment to update |
| `text`   | `string`| **Required**. text of comment |

#### - Destroy comment

```http
  DELETE /api/comment/{id}
```

| Parameter | Type     | Description                       |
| :-------- | :------- | :-------------------------------- |
| `id`      | `integer` | **Required**. id of comment to destroy |

###  Upload
#### - Upload post image

```http
  POST /api/upload/upload-post-image
```

| Parameter | Type     | Description                       |
| :-------- | :------- | :-------------------------------- |
| `image`      | `image` | **Required**. |

#### - Upload profile image

```http
  POST /api/upload/upload-profile-image
```

| Parameter | Type     | Description                       |
| :-------- | :------- | :-------------------------------- |
| `image`      | `image` | **Required**. |

###  Auth
#### - Register

```http
  POST /api/register
```

| Parameter | Type     | Description                       |
| :-------- | :------- | :-------------------------------- |
| `name`      | `string` | **Required**. |
| `email`      | `string` | **Required**. |
| `password`      | `string` | **Required**. |
| `password_confirmation`      | `string` | **Required**. |

#### - Login

```http
  POST /api/login
```

| Parameter | Type     | Description                       |
| :-------- | :------- | :-------------------------------- |
| `email`      | `string` | **Required**. |
| `password`      | `string` | **Required**. |

#### - Logout

```http
  POST /api/logout
```

| Parameter | Type     | Description                       |
| :-------- | :------- | :-------------------------------- |
| `token`      | `string` | **Required**. user token |


#### - Forgot password

```http
  POST /api/forgot-password
```

| Parameter | Type     | Description                       |
| :-------- | :------- | :-------------------------------- |
| `email`      | `string` | **Required**. |

#### - Reset password

```http
  POST /api/reset-password
```

| Parameter | Type     | Description                       |
| :-------- | :------- | :-------------------------------- |
| `email`      | `string` | **Required**. |
| `password`      | `string` | **Required**. |
| `password_confirmation`      | `string` | **Required**. |
| `token`      | `string` | **Required**. token email to user |

###  User
#### - Get all users

```http
  POST /api/user
```
#### - Get all admin

```http
  POST /api/user/adminsList
```

#### - Get user posts

```http
  POST /api/user/{id}/posts
```

| Parameter | Type     | Description                       |
| :-------- | :------- | :-------------------------------- |
| `id`      | `integer` | **Required**. id of user |

#### - Get user comments

```http
  POST /api/user/{id}/comments
```

| Parameter | Type     | Description                       |
| :-------- | :------- | :-------------------------------- |
| `id`      | `integer` | **Required**. id of user |


#### - Get user liked posts

```http
  POST /api/user/{id}/liked-posts
```

| Parameter | Type     | Description                       |
| :-------- | :------- | :-------------------------------- |
| `id`      | `integer` | **Required**. id of user |

#### - Promote user to admin

```http
  POST /api/user/{id}/promote-to-Admin
```

| Parameter | Type     | Description                       |
| :-------- | :------- | :-------------------------------- |
| `id`      | `integer` | **Required**. id of user |
| `password`      | `string` | **Required**. password of admin |

#### - Demote admin to user

```http
  POST /api/user/{id}/demote-to-User
```

| Parameter | Type     | Description                       |
| :-------- | :------- | :-------------------------------- |
| `id`      | `integer` | **Required**. id of admin that want to be user  |
| `password`      | `string` | **Required**. password of admin |

###  Profile
#### - Update profile

```http
  POST /api/profile/update
```
>   if you dont want to change each item, just send actual value.

| Parameter | Type     | Description                       |
| :-------- | :------- | :-------------------------------- |
| `name`      | `string` | **Required**. |
| `profile_image_url`      | `string` | **Required**. Retrive form /api/upload/upload-profile-image |

#### - Change password

```http
  POST /api/profile/change-password
```
| Parameter | Type     | Description                       |
| :-------- | :------- | :-------------------------------- |
| `current_password`      | `string` | **Required**. |
| `new_password`      | `string` | **Required**. |
| `new_password_confirmation`      | `string` | **Required**. |
  
