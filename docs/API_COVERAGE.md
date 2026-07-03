# API Coverage

Every route exposed by the MailChannels Email API v1 has a corresponding SDK
method. The table below maps each endpoint to its SDK call.

| Method | Path                                                | SDK method                                          |
| ------ | --------------------------------------------------- | --------------------------------------------------- |
| POST   | `/send`                                             | `$client->emails->send($params)`                    |
| POST   | `/send-async`                                       | `$client->emails->queue($params)`                   |
| POST   | `/check-domain`                                     | `$client->checkDomain->check($domain)`              |
| POST   | `/domains/{domain}/dkim-keys`                       | `$client->dkim->create($domain, $selector)`         |
| GET    | `/domains/{domain}/dkim-keys`                       | `$client->dkim->list($domain)`                      |
| PATCH  | `/domains/{domain}/dkim-keys/{selector}`            | `$client->dkim->updateStatus($domain, $selector, $status)` |
| POST   | `/domains/{domain}/dkim-keys/{selector}/rotate`     | `$client->dkim->rotate($domain, $selector, $newSelector)` |
| GET    | `/custom-tracking-domains`                          | `$client->customTrackingDomains->list(...)`         |
| POST   | `/custom-tracking-domains`                          | `$client->customTrackingDomains->create($name, $hostname, $scope)` |
| PATCH  | `/custom-tracking-domains/{hostname}/{scope}`       | `$client->customTrackingDomains->update($hostname, $scope, ...)` |
| DELETE | `/custom-tracking-domains/{hostname}/{scope}`       | `$client->customTrackingDomains->delete($hostname, $scope)` |
| GET    | `/metrics/engagement`                               | `$client->metrics->engagement(...)`                 |
| GET    | `/metrics/performance`                              | `$client->metrics->performance(...)`                |
| GET    | `/metrics/recipient-behaviour`                      | `$client->metrics->recipientBehaviour(...)`         |
| GET    | `/metrics/volume`                                   | `$client->metrics->volume(...)`                     |
| GET    | `/metrics/senders/{sender_type}`                    | `$client->metrics->senders($type, ...)`             |
| POST   | `/sub-account`                                      | `$client->subAccounts->create(...)`                 |
| GET    | `/sub-account`                                      | `$client->subAccounts->list(...)`                   |
| DELETE | `/sub-account/{handle}`                             | `$client->subAccounts->delete($handle)`             |
| POST   | `/sub-account/{handle}/activate`                    | `$client->subAccounts->activate($handle)`           |
| POST   | `/sub-account/{handle}/suspend`                     | `$client->subAccounts->suspend($handle)`            |
| GET    | `/sub-account/{handle}/usage`                       | `$client->subAccounts->retrieveUsage($handle)`      |
| POST   | `/sub-account/{handle}/api-key`                     | `$client->subAccounts->apiKeys->create($handle)`    |
| GET    | `/sub-account/{handle}/api-key`                     | `$client->subAccounts->apiKeys->list($handle)`      |
| DELETE | `/sub-account/{handle}/api-key/{id}`                | `$client->subAccounts->apiKeys->delete($handle, $id)` |
| POST   | `/sub-account/{handle}/smtp-password`               | `$client->subAccounts->smtpPasswords->create($handle)` |
| GET    | `/sub-account/{handle}/smtp-password`               | `$client->subAccounts->smtpPasswords->list($handle)` |
| DELETE | `/sub-account/{handle}/smtp-password/{id}`          | `$client->subAccounts->smtpPasswords->delete($handle, $id)` |
| PUT    | `/sub-account/{handle}/limit`                       | `$client->subAccounts->limits->set($handle, sends: ...)` |
| GET    | `/sub-account/{handle}/limit`                       | `$client->subAccounts->limits->retrieve($handle)`   |
| DELETE | `/sub-account/{handle}/limit`                       | `$client->subAccounts->limits->delete($handle)`     |
| POST   | `/suppression-list`                                 | `$client->suppressions->create($entries)`           |
| GET    | `/suppression-list`                                 | `$client->suppressions->list(...)`                  |
| DELETE | `/suppression-list/recipients/{recipient}`          | `$client->suppressions->delete($recipient)`         |
| GET    | `/usage`                                            | `$client->usage->retrieve()`                        |
| POST   | `/webhook`                                          | `$client->webhooks->create($endpoint)`              |
| GET    | `/webhook`                                          | `$client->webhooks->list()`                         |
| DELETE | `/webhook`                                          | `$client->webhooks->deleteAll()`                    |
| GET    | `/webhook-batch`                                    | `$client->webhooks->batches(...)`                   |
| POST   | `/webhook-batch/{batch_id}/resend`                  | `$client->webhooks->resendBatch($id, $handle)`      |
| GET    | `/webhook/public-key`                               | `$client->webhooks->publicKey($keyId)`              |
| POST   | `/webhook/validate`                                 | `$client->webhooks->validate()`                     |
