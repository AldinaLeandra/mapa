@resetFixture @countrycodes
Feature: Testing the Country Codes API

    Scenario: Retrieve All Country Codes
        Given that I want to get all "CountryCodes"
        And that the request "Authorization" header is "Bearer testadminuser"
        When I request "/country-codes"
        Then the response is JSON
        And the response has a "total_count" property
        And the type of the "total_count" property is "numeric"
        And the "total_count" property equals "246"
        Then the guzzle status code should be 200


    @resetFixture
    Scenario: Basic User users cannot get Country Codes
        Given that I want to get all "CountryCodes"
        And that the request "Authorization" header is "Bearer testbasicuser"
        When I request "/country-codes"
        Then the response is JSON
        Then the guzzle status code should be 400

    @resetFixture
    Scenario: Anonymous users cannot get Country Codes
        Given that I want to get all "CountryCodes"
        And that the request "Authorization" header is "Bearer testanon"
        When I request "/country-codes"
        Then the response is JSON
        Then the guzzle status code should be 400
