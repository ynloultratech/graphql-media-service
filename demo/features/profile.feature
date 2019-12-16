Feature: Profile

  Scenario: Add profile with photo
    When upload file "avatar.jpg"
    Then the response is OK
    And grab "{response.data.uploadFile.id}" to use as "photoId"
    And grab "{response.data.uploadFile.name}" to use as "fileName"
    And should exist in repository "App:File" a record matching:
    """
    name: '{fileName}'
    status: NEW
    storage: public_files
    """
    Given the operation named "AddProfileWithPhoto"
    And variable "name" is "{faker.name}"
    And variable "email" is "{faker.email}"
    And variable "photoId" is "{photoId}"
    When send
    And "{response.data.profiles.add.node.photo.url}" should not be null
    And compare uploaded file "{response.data.profiles.add.node.photo.url}" with "avatar.jpg"
    And should exist in repository "App:File" a record matching:
    """
    name: 'photo.jpeg'
    status: IN_USE
    storage: public_files
    """

  Scenario: Set photo to existent profile
    When upload file "avatar.jpg"
    Then the response is OK
    And grab "{response.data.uploadFile.id}" to use as "photoId"
    And grab "{response.data.uploadFile.name}" to use as "fileName"
    And should exist in repository "App:File" a record matching:
    """
    name: '{fileName}'
    status: NEW
    storage: public_files
    """
    Given the operation named "SetProfilePhoto"
    And variable "id" is "#profile1"
    And variable "photoId" is "{photoId}"
    When send
    And "{response.data.profiles.update.node.photo.url}" should not be null
    And compare uploaded file "{response.data.profiles.update.node.photo.url}" with "avatar.jpg"
    And should exist in repository "App:File" a record matching:
    """
    name: 'photo.jpeg'
    status: IN_USE
    storage: public_files
    """
    And remove file "{photoId}"
