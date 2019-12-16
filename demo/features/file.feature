Feature: File

  Scenario: Upload File
    When upload file "sample.txt"
    Then the response is OK
    And grab "{response.data.uploadFile.id}" to use as "fileId"
    And grab "{response.data.uploadFile.name}" to use as "fileName"
    Then should exist in repository "App:File" a record matching:
    """
    name: '{fileName}'
    status: NEW
    storage: public_files
    """
    Given the operation named "GetFile"
    And variable "id" is "{fileId}"
    When send
    Then the response is OK
    And "{response.data.node.id}" should be equal to "{fileId}"
    And "{response.data.node.name}" should be equal to "{fileName}"
    And "{response.data.node.size}" should be equal to "16"
    And "{response.data.node.url}" should not be null

    Then compare uploaded file "{response.data.node.url}" with "sample.txt"
    And remove file "{fileId}"