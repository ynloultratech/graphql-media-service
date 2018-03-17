Feature: File

  Scenario: Upload File
    When upload file "sample.txt"
    Then the response status code should be 201
    And grab "{response.data.id}" to use as "fileId"
    And restart client

    Given the operation named "GetFile"
    And variable "id" is "{fileId}"
    When send
    Then the response is OK
    And "{response.data.node.id}" should be equal to "{fileId}"
    And "{response.data.node.name}" should be equal to "sample.txt"
    And "{response.data.node.size}" should be equal to "16"
    And "{response.data.node.url}" should not be null

    Then compare uploaded file "{response.data.node.url}" with "sample.txt"
    And remove file "{fileId}"

  Scenario:
    When upload file "avatar.jpg"
    Then the response status code should be 201
    And grab "{response.data.id}" to use as "photoId"
    And should exist in repository "AppBundle:File" a record matching:
    """
    name: avatar.jpg
    status: NEW
    storage: public_files
    """
    And restart client

    Given the operation named "SetProfilePhoto"
    And variable "id" is "#profile1"
    And variable "photoId" is "{photoId}"
    When send
    And "{response.data.updateProfile.node.photo.url}" should not be null
    And compare uploaded file "{response.data.updateProfile.node.photo.url}" with "avatar.jpg"
    And should exist in repository "AppBundle:File" a record matching:
    """
    name: avatar.jpg
    status: IN_USE
    storage: private_files
    """

    And remove file "{photoId}"
