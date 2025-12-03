import os
import os.path
import shutil
import json
import datetime
import requests
import sys

def cleanupPulls():
    params = {'state':'open', 'per_page':'100'}

    print("Fetching all open Pull Requests...")

    url = apiBase + '/pulls'

    response = requests.get(url, headers=headers, params=params)

    pulls = response.json()

    for pull in pulls:
        title = pull.get('title')

        updatedAt = pull.get('updated_at')

        lastUpdated = datetime.datetime.strptime(updatedAt, "%Y-%m-%dT%H:%M:%SZ")

        delta = now - lastUpdated

        days = delta.days

        if days >= 30:
            pullNumber = pull.get('number')

            print('Closing Pull Request https://github.com/FedEx/' + repoName + '/pull/' + str(pullNumber) + ' due to inactivity (' + str(days) + ' days)')

            #Close inactive pulls first since stale branches cannot be deleted if a pull request is open
            url = apiBase + '/pulls/' + str(pullNumber)

            response = requests.patch(url, headers=headers, json={"state": "closed"})

def cleanupBranches():
    #Fetch metadata for all unprotected branches
    url = apiBase + '/branches'

    params = {'protected':'false','per_page':'100'}

    print("Fetching all Unprotected Branches...")

    response = requests.get(url, headers=headers, params=params)

    branches = response.json()

    page = response.links.get('next');

    for miniBranch in branches:
        branchName = miniBranch.get('name')

        url = apiBase + '/branches/' + branchName

        #Retrieve the detailed branch info
        response = requests.get(url, headers=headers)

        branch = response.json()

        commit = branch.get('commit')

        miniCommit = commit.get('commit')

        committer = miniCommit.get('committer')

        date = committer.get('date')

        committerName = committer.get('name')

        commitDate = datetime.datetime.strptime(date, "%Y-%m-%dT%H:%M:%SZ")

        delta = now - commitDate

        days = delta.days
        
        if days >= 30:
            print('Deleting branch due to inactivity (' + str(days) + ' days) - ' + committerName + ': ' + branchName)

            url  = apiBase + '/git/refs/heads/' + branchName

            requests.delete(url, headers=headers)

now = datetime.datetime.now()

token = os.environ.get('GITHUB_TOKEN')

#Fetch all open pull requests
repoName = 'eai-3537131-fxo-ecommerce-platform'

apiBase = 'https://api.github.com/repos/FedEx/' + repoName

headers = {'Accept':'application/vnd.github+json', 'X-GitHub-Api-Version':'2022-11-28', 'Authorization':'Bearer ' + token}

cleanupPulls()

cleanupBranches()

