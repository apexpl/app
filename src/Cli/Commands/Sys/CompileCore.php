<?php
declare(strict_types = 1);

namespace Apex\App\Cli\Commands\Sys;

use Apex\App\Cli\{Cli, CliHelpScreen};
use Apex\App\Sys\Utils\Io;
use Apex\App\Network\Stores\PackagesStore;
use Apex\App\Interfaces\Opus\CliCommandInterface;
use Symfony\Component\Yaml\Yaml;

/**
 * Compile core
 */
class CompileCore implements CliCommandInterface
{

    #[Inject(Io::class)]
    private Io $io;

    #[Inject(PackagesStore::class)]
    private PackagesStore $pkg_store;

    // Templates
    private array $templates = [
        'cluster.yml' => 'CiMjIyMjIyMjIyMjIyMjIyMjIyMjCiMgQ2x1c3RlciByb3V0aW5nIGZpbGUKIyAKIyBZb3Ugd2lsbCBnZW5lcmFsbHkgbmV2ZXIgbmVlZCB0aGlzIGZpbGUsIHVubGVzcyB5b3UgbmVlZCB0byAKIyBpbXBsZW1lbnQgcXVldXMgb3Igb3RoZXIgaG9yaXpvbnRhbCBzY2FsaW5nIGZ1bmN0aW9uYWxpdHkuICBQbGVhc2UgCiMgcmVmZXIgdG8gdGhlIGRvY3VtZW50YXRpb24gZm9yIGRldGFpbHMuCiMjIyMjIyMjIyMjIyMjIyMjIyMjCgpyb3V0ZXM6CiAgcnBjLmRlZmF1bHQ6CiAgICB0eXBlOiBycGMKICAgIGluc3RhbmNlczogYWxsCiAgICByb3V0aW5nX2tleXM6IAogICAgICBhbGw6IEFwcFx+cGFja2FnZS50aXRsZX5cTGlzdGVuZXJzXH5tb2R1bGUudGl0bGV+CgoK',
        'container.php' => 'PD9waHAKCnVzZSBBcGV4XFN2Y1xEaTsKdXNlIExlYWd1ZVxGbHlzeXN0ZW1cRmlsZXN5c3RlbTsKdXNlIEFwZXhcRGJcSW50ZXJmYWNlc1xEYkludGVyZmFjZTsKdXNlIEFwZXhcQ2x1c3RlclxJbnRlcmZhY2VzXEJyb2tlckludGVyZmFjZTsKdXNlIEFwZXhcQXBwXEludGVyZmFjZXNcUm91dGVySW50ZXJmYWNlOwp1c2UgQXBleFxNZXJjdXJ5XEludGVyZmFjZXNce0VtYWlsZXJJbnRlcmZhY2UsIFNtc0NsaWVudEludGVyZmFjZSwgRmlyZWJhc2VDbGllbnRJbnRlcmZhY2UsIFdzQ2xpZW50SW50ZXJmYWNlfTsKdXNlIFBzclxMb2dcTG9nZ2VySW50ZXJmYWNlOwp1c2UgUHNyXENhY2hlXENhY2hlSXRlbVBvb2xJbnRlcmZhY2U7CnVzZSBQc3JcU2ltcGxlQ2FjaGVcQ2FjaGVJbnRlcmZhY2U7CnVzZSBQc3JcSHR0cFxDbGllbnRcQ2xpZW50SW50ZXJmYWNlIGFzIEh0dHBDbGllbnRJbnRlcmZhY2U7CnVzZSBBcGV4XENvbnRhaW5lclxJbnRlcmZhY2VzXEFwZXhDb250YWluZXJJbnRlcmZhY2U7CnVzZSBNb25vbG9nXExvZ2dlcjsKdXNlIE1vbm9sb2dcSGFuZGxlclxTdHJlYW1IYW5kbGVyOwp1c2UgU3ltZm9ueVxDb21wb25lbnRcQ2FjaGVcUHNyMTZDYWNoZTsKCi8qKgogKiBUaGlzIGZpbGUgYWxsb3dzIHlvdSB0byBzd2l0Y2ggb3V0IHRoZSBpbXBsZW1lbnRhdGlvbnMgdXNlZCBmb3IgdmFyaW91cyAKICogUFNSIGNvbXBsaWFudCBhbmQgb3RoZXIgc2VydmljZXMuICBZb3UgbWF5IGNoYW5nZSB0aGUgaW1wbGVtZW50YXRpb25zIGJlbG93IHRvIAogKiBhbnl0aGluZyB5b3Ugd2lzaCBhcyBsb25nIGFzIGl0IHN0aWxsIGltcGxlbWVudHMgdGhlIGFwcHJvcHJpYXRlIGludGVyZmFjZS4KICovCnJldHVybiBbCgogICAgLyoqCiAgICAgKiBTUUwgZGF0YWJhc2UgZHJpdmVyLiAgVGhpcyBjYW4gYmUgY2hhbmdlZCB0byBlaXRoZXIgUG9zdGdyZVNRTCBvciBTUUxpdGUsIAogICAgICogYnV0IG11c3QgYmUgY2hhbmdlZCBiZWZvcmUgaW5zdGFsbGF0aW9uLgogICAgICovCiAgICBEYkludGVyZmFjZTo6Y2xhc3MgPT4gXEFwZXhcRGJcRHJpdmVyc1xteVNRTFxteVNRTDo6Y2xhc3MsIAoKICAgIC8qKgogICAgICogUFNSLTE4IGNvbXBsaWFudCBIVFRQIGNsaWVudC4KICAgICAqLwogICAgSHR0cENsaWVudEludGVyZmFjZTo6Y2xhc3MgPT4gZnVuY3Rpb24gKCkgeyByZXR1cm4gbmV3IFxHdXp6bGVIdHRwXENsaWVudChbJ3ZlcmlmeScgPT4gZmFsc2VdKTsgfSwgCgogICAgLyoqCiAgICAgKiBFLW1haWxlci4gIElmIHByZWZlcnJlZCwgUGhwTWFpbGVyIGFuZCBTeW1mb255IE1haWxlciBhZGFwdGVycyBhcmUgYXZhaWxhYmxlIHdpdGhpbiAKICAgICAqIHRoZSBBcGV4XEFwcFxBZGFwdGVyc1xFbWFpbCBuYW1lc3BhY2UuICBTZWUgZG9jdW1lbnRhdGlvbiBmb3IgZGV0YWlscy4KICAgICAqLwogICAgRW1haWxlckludGVyZmFjZTo6Y2xhc3MgPT4gXEFwZXhcTWVyY3VyeVxFbWFpbFxFbWFpbGVyOjpjbGFzcywKCiAgICAvKioKICAgICAqIEluc3RhbmNlIG9mIGxlYWd1ZS9maWxlc3lzdGVtIGZvciBzdG9yYWdlIG9mIGZpbGVzIGFuZCBjb250ZW50LiAgRGVmYXVsdHMgdG8gbG9jYWwgc3RvcmFnZSBvZiAvc3RvcmFnZS8gZGlyZWN0b3J5LCAKICAgICAqIGFsdGhvdWdoIGNhbiBiZSBlYXNpbHkgY2hhbmdlZCB0byBBV1MgUzMsIERpZ2l0YWxPY2VhbiwgYW5kIG90aGVycy4KICAgICAqLwogICAgRmlsZXN5c3RlbTo6Y2xhc3MgPT4gZnVuY3Rpb24oKSB7IAogICAgICAgIHJldHVybiBcQXBleFxTdG9yYWdlXFN0b3JhZ2U6OmluaXQoJ2xvY2FsJywgWydkaXInID0+IFNJVEVfUEFUSCAuICcvc3RvcmFnZSddKTsKICAgIH0sCgogICAgLyoqCiAgICAgKiBIVFRQIHJvdXRlciB0aGF0IGlzIHVzZWQsIGZvciBjYXNlcyB3aGVyZSB5b3UgcHJlZmVyIHRvIGltcGxlbWVudCB5b3VyIG93biByb3V0ZXIuCiAgICAgKi8gCiAgICBSb3V0ZXJJbnRlcmZhY2U6OmNsYXNzID0+IFxBcGV4XEFwcFxCYXNlXFJvdXRlclxSb3V0ZXI6OmNsYXNzLAoKICAgIC8qKgogICAgICogUFNSLTYgY29tcGxpYW50IGNhY2hlLiAgSXMgc2V0IHRvIG51bGwgaWYgY2FjaGluZyB3aXRoaW4gY29uZmlndXJhdGlvbiBpcyBkaXNhYmxlZC4KICAgICAqLwogICAgQ2FjaGVJdGVtUG9vbEludGVyZmFjZTo6Y2xhc3MgPT4gW1xTeW1mb255XENvbXBvbmVudFxDYWNoZVxBZGFwdGVyXFJlZGlzQWRhcHRlcjo6Y2xhc3MsIFsnbmFtZXNwYWNlJyA9PiAnY2FjaGUnXV0sCiAgICAnc3lydXMuY2FjaGVfdHRsJyA9PiAzMDAsIAoKICAgIC8qKgogICAgICogUFNSLTE2IGNvbXBsaWFudCBjYWNoZS4gIElzIHNldCB0byBudWxsIGlmIGNhY2hpbmcgd2l0aGluIGNvbmZpZ3VyYXRpb24gaXMgZGlzYWJsZWQuCiAgICAgKi8KICAgIGNhY2hlSW50ZXJmYWNlOjpjbGFzcyA9PiBmdW5jdGlvbigpIHsKICAgICAgICAkcHNyNmNhY2hlID0gRGk6OmdldChDYWNoZUl0ZW1Qb29sSW50ZXJmYWNlOjpjbGFzcyk7CiAgICAgICAgcmV0dXJuIG5ldyBQc3IxNkNhY2hlKCRwc3I2Y2FjaGUpOwogICAgfSwKCiAgICAvKioKICAgICAqIEdlbmVyYWxseSBvbmx5IG5lZWRzIHRvIGJlIGNoYW5nZWQgaWYgeW91IHdpc2ggdG8gZW5hYmxlIGhvcml6b250YWwgc2NhbGluZyB2aWEgUmFiYml0TVEgCiAgICAgKiBvciBvdGhlciBtZXNzYWdlIGJyb2tlcnMuICBQbGVhc2UgcmVmZXIgdG8gZG9jdW1lbnRhdGlvbiBmb3IgZGV0YWlscy4KICAgICAqLwogICAgQnJva2VySW50ZXJmYWNlOjpjbGFzcyA9PiBBcGV4XENsdXN0ZXJcQnJva2Vyc1xMb2NhbDo6Y2xhc3MsIAogICAgJ2NsdXN0ZXIudGltZW91dF9zZWNvbmRzJyA9PiAzLCAgCgogICAgLyoqCiAgICAgKiBQU1ItMyBjb21wbGlhbnQgbG9nZ2VyCiAgICAgKi8KICAgIExvZ2dlckludGVyZmFjZTo6Y2xhc3MgPT4gZnVuY3Rpb24oKSB7IAogICAgICAgICRsb2dkaXIgPSBfX0RJUl9fIC4gJy8uLi9zdG9yYWdlL2xvZ3MnOwogICAgICAgIHJldHVybiBuZXcgTG9nZ2VyKCdhcHAnLCBbCiAgICAgICAgICAgIG5ldyBTdHJlYW1IYW5kbGVyKCRsb2dkaXIgLiAnL2RlYnVnLmxvZycsIExvZ2dlcjo6REVCVUcpLCAKICAgICAgICAgICAgbmV3IFN0cmVhbUhhbmRsZXIoJGxvZ2RpciAuICcvYXBwLmxvZycsIExvZ2dlcjo6SU5GTyksIAogICAgICAgICAgICBuZXcgU3RyZWFtSGFuZGxlcigkbG9nZGlyIC4gJy9lcnJvci5sb2cnLCBMb2dnZXI6OkVSUk9SKSAKICAgICAgICBdKTsKICAgIH0sIAoKICAgIC8qKgogICAgICogTWVzc2FnaW5nIGltcGxlbWVudHMgZm9yIFNNUyBjbGllbnQsIEZpcmViYXNlLCBhbmQgd2ViIHNvY2tldCBjbGllbnQuCiAgICAgKi8KICAgIFNtc0NsaWVudEludGVyZmFjZTo6Y2xhc3MgPT4gXEFwZXhcTWVyY3VyeVxTTVNcTmV4bW86OmNsYXNzLAogICAgRmlyZWJhc2VDbGllbnRJbnRlcmZhY2U6OmNsYXNzID0+IFxBcGV4XE1lcmN1cnlcRmlyZWJhc2VcRmlyZWJhc2U6OmNsYXNzLAogICAgV3NDbGllbnRJbnRlcmZhY2U6OmNsYXNzID0+IFxBcGV4XE1lcmN1cnlcV2Vic29ja2V0XFdzQ2xpZW50OjpjbGFzcywKCiAgICAvKioKICAgICAqIENvb2tpZSBvcHRpb25zIGFycmF5LiAgfmRvbWFpbn4gaXMgcmVwbGFjZWQgd2l0aCB0aGUgCiAgICAgKiBkb21haW4gbmFtZSBpbiB5b3VyIHNldHRpbmdzLgogICAgICovCiAgICAnYXJtb3IuY29va2llX3ByZWZpeCcgPT4gJ2FybW9yXycsIAogICAgJ2FybW9yLmNvb2tpZScgPT4gWwogICAgICAgICdwYXRoJyA9PiAnLycsIAogICAgICAgICdkb21haW4nID0+ICd+ZG9tYWluficsCiAgICAgICAgJ3NlY3VyZScgPT4gdHJ1ZSwgCiAgICAgICAgJ2h0dHBvbmx5JyA9PiBmYWxzZSwKICAgICAgICAnc2FtZXNpdGUnID0+ICdzdHJpY3QnCiAgICBdLAoKICAgIC8qKgogICAgICogRGVwZW5kZW5jeSBpbmplY3Rpb24gY29udGFpbmVyLCBhbmQgc2hvdWxkIGdlbmVyYWxseSBuZXZlciBiZSBjaGFuZ2VkLiAgTXVzdCBiZSBhIGNsb3N1cmUsIGFuZCBpbXBsZW1lbnQgdGhlIAogICAgICogQXBleENvbnRhaW5lckludGVyZmFjZS4gIFBsZWFzZSBzZWUgZG9jdW1lbnRhdGlvbiBmb3IgZGV0YWlscy4KICAgICAqLwogICAgQXBleENvbnRhaW5lckludGVyZmFjZTo6Y2xhc3MgPT4gZnVuY3Rpb24oKSB7IAogICAgICAgIHJldHVybiBuZXcgXEFwZXhcQ29udGFpbmVyXENvbnRhaW5lcih1c2VfYXR0cmlidXRlczogdHJ1ZSk7CiAgICB9CgpdOwoK',
        'env' => 'CiMjIyMjIyMjIyMjIyMjIyMjIyMjCiMgQXBleCAuZW52IGZpbGUuCiMKIyBBcGV4IGhhcyBub3QgeWV0IGJlZW4gaW5zdGFsbGVkLCBoZW5jZSB0aGlzIGZpbGUgaXMgZW1wdHkuCiMKIyBQbGVhc2UgcnVuIC4vYXBleCB3aXRoaW4gdGhpcyBkaXJlY3RvcnkgdG8gaW5pdGlhdGUgdGhlIGluc3RhbGxhdGlvbiB3aXphcmQuCiMKIyMjIyMjIyMjIyMjIyMjIyMjIyMKCgo=',
        'routes.yml' => 'CiMjIyMjIyMjIyMKIyBSb3V0ZXMKIwojIFRoaXMgZmlsZSBoYXMgYmVlbiBhdXRvLWdlbmVyYXRlZCwgYnV0IHlvdSBtYXkgbW9kaWZ5IGFzIGRlc2lyZWQgYmVsb3cuICBQbGVhc2UgcmVmZXIgdG8gdGhlIGRldmVsb3BlciAKIyBkb2N1bWVudGF0aW9uIGZvciBkZXRhaWxzIG9uIHRoZSBlbnRyaWVzIHdpdGhpbiB0aGlzIGZpbGUuCiMjIyMjIyMjIyMKCnJvdXRlczoKICAgIGRlZmF1bHQ6IFB1YmxpY1NpdGUKCgo=',
        'site.yml' => 'CiMjIyMjIyMjIyMKIyBTaXRlIENvbmZpZwojCiMgVGhpcyBmaWxlIGhhcyBiZWVuIGF1dG8tZ2VuZXJhdGVkLCBidXQgeW91IG1heSBtb2RpZnkgYXMgZGVzaXJlZCBiZWxvdy4gIFBsZWFzZSByZWZlciB0byB0aGUgZGV2ZWxvcGVyIAojIGRvY3VtZW50YXRpb24gZm9yIGRldGFpbHMgb24gdGhlIGVudHJpZXMgd2l0aGluIHRoaXMgZmlsZS4KIyMjIyMjIyMjIwoKdGhlbWVzOgogICAgZGVmYXVsdDogZGVmYXVsdAoKdXNlcl90eXBlczoKCnBhZ2VfdmFyczoKCiAgICB0aXRsZToKICAgICAgICBkZWZhdWx0OiBBcGV4CgogICAgbGF5b3V0czoKICAgICAgICBpbmRleC5odG1sOiBob21lcGFnZQogICAgICAgIGRlZmF1bHQ6IGRlZmF1bHQKCm5vY2FjaGVfcGFnZXM6Cgpub2NhY2hlX3RhZ3M6CiAgICAtIGRhdGFfdGFibGUKICAgIC0gY2FsbG91dHMKICAgIC0gcGxhY2Vob2xkZXIK',
        'view_index.html' => 'CjxoMT5XZWxjb21lIHRvIEFwZXghPC9oMT4KCjxwPkNvbmdyYXR1bGF0aW9ucywgaWYgeW91J3JlIHZpZXdpbmcgdGhpcyBwYWdlIEFwZXggaGFzIGJlZW4gc3VjY2Vzc2Z1bGx5IGluc3RhbGxlZCBhbmQgaXMgcmVhZHkgZm9yIHVzZSEgIFRvIGhlbHAgeW91IGdldCBzdGFydGVkLCBjbGljayBvbiBvbmUgb2YgdGhlIGJlbG93IGxpbmtzIGZvciB1c2VmdWwgZG9jdW1lbnRhdGlvbiBhbmQgY29tbXVuaXR5IHN1cHBvcnQuPC9wPgoKPHM6Ym94bGlzdD4KCiAgICA8czppdGVtIGhyZWY9Imh0dHBzOi8vYXBleHBsLmlvL2d1aWRlcy8iIHRpdGxlPSJMZWFybiBCeSBFeGFtcGxlIEd1aWRlcyI+CiAgICAgICAgR2V0IHVwIGFuZCBydW5uaW5nIGRldmVsb3Bpbmcgd2l0aCBBcGV4IHVzaW5nIHRoZSBlYXN5IHRvIGZvbGxvdyBsZWFybiBieSBleGFtcGxlIGd1aWRlcy4KICAgIDwvczppdGVtPgoKICAgIDxzOml0ZW0gaHJlZj0iaHR0cHM6Ly9hcGV4cGwuaW8vZG9jcy8iIHRpdGxlPSJEZXZlbG9wZXIgRG9jdW1lbnRhdGlvbiI+CiAgICAgICAgRnVsbCBhbmQgY29tcHJlaGVuc2l2ZSBkZXZlbG9wZXIgZG9jdW1lbnRhdGlvbiBjb3ZlcmluZyBhbGwgZmFjZXRzIG9mIEFwZXguCiAgICA8L3M6aXRlbT4KCiAgICA8czppdGVtIGhyZWY9Imh0dHBzOi8vcmVkZGl0LmNvbS9yL2FwZXhwbCIgdGl0bGU9IlJlZGRpdCAoL3IvYXBleHBsKSI+CiAgICAgICAgSGF2aW5nIGlzc3VlcywgYW5kIG5lZWQgc29tZSBoZWxwPyAgUG9zdCBvbiB0aGUgL3IvYXBleHBsIHN1YiBSZWRkaXQgZm9yIGEgcHJvbXB0IGFuZCBoZWxwZnVsIHJlc3BvbnNlLgogICAgPC9zOml0ZW0+Cgo8L3M6Ym94bGlzdD4KCgo=',
        'view_index.php' => 'PD9waHAKZGVjbGFyZShzdHJpY3RfdHlwZXMgPSAxKTsKCm5hbWVzcGFjZSBWaWV3czsKCnVzZSBBcGV4XFN2Y1xWaWV3OwoKLyoqCiAqIFJlbmRlciB0aGUgdGVtcGxhdGUuCiAqLwpjbGFzcyBpbmRleAp7CgogICAgLyoqCiAgICAgKiBSZW5kZXIKICAgICAqLwogICAgcHVibGljIGZ1bmN0aW9uIHJlbmRlcihWaWV3ICR2aWV3KTp2b2lkCiAgICB7CgogICAgfQoKfQoKCg=='
    ];

    /**
     * Process
     */
    public function process(Cli $cli, array $args):void
    {

        // Get args
        $opt = $cli->getArgs(['dest']);
        $dest = $opt['dest'] ?? '/tmp/apex-core';

        // Make directory
        $this->io->createBlankDir($dest);

        // Define root dirs
        $root_dirs = [
            'boot', 
            'docs', 
            'etc', 
            'public/themes', 
            'public/plugins', 
            'src/HttpControllers',
            'storage/logs', 
            'tests', 
            'views/html',
            'views/php',
            'views/themes'
        ];

        // Create root dirs
        foreach ($root_dirs as $dir) { 
            mkdir("$dest/$dir", 0755, true);
        }

        // Set base files
        $base_files = [
            'apex',
            'composer.json',
            'docker-compose.yml',
            'install_example.yml',
            'License.txt',
            'phpunit.xml',
            'Readme.md',
            'etc/Core',
            'boot/init',
            'boot/docker',
            'views/themes/default',
            'public/index.php',
            'public/.htaccess',
            'public/robots.txt',
            'public/themes/default',
            'tests/Readme.md',
            'src/HttpControllers/PublicSite.php',
            'views/html/index.html',
            'views/html/404.html',
            'views/html/500.html',
            'views/html/500_generic.html',
            'views/php/index.php'
        ];

        // Copy base files
        foreach ($base_files as $file) { 
            $source = SITE_PATH . '/' . $file;
            system("cp -R $source $dest/$file");
        }

        // Save template files
        file_put_contents("$dest/boot/cluster.yml", base64_decode($this->templates['cluster.yml']));
        file_put_contents("$dest/boot/container.php", base64_decode($this->templates['container.php']));
        file_put_contents("$dest/boot/routes.yml", base64_decode($this->templates['routes.yml']));
        file_put_contents("$dest/boot/site.yml", base64_decode($this->templates['site.yml']));
        file_put_contents("$dest/.env", base64_decode($this->templates['env']));
        file_put_contents("$dest/views/html/index.html", base64_decode($this->templates['view_index.html']));
        file_put_contents("$dest/views/php/index.php", base64_decode($this->templates['view_index.php']));

        // Create yaml files
        $this->createYamlFiles($dest);

        // Success
        $cli->success("Successfully compiled the core Apex package to $dest\r\n\r\n");
    }

    /**
     * Create yaml files
     */
    private function createYamlFiles(string $dest):void
    {

        // Get core version
        $pkg = $this->pkg_store->get('core');

        // Set config.yml yaml
        $config_yaml = [
            'packages' => [],
            'repos' => []
        ];

        // Core package
            $config_yaml['packages']['core'] = [
            'type' => 'package',
            'version' => $pkg->getVersion(),
            'author' => 'apex',
            'local_user' => '',
            'repo_alias' => 'apex',
            'installed_at' => time()
        ];

        // Repo entry
        $config_yaml['repos']['apex'] = [
            'host' => 'api.apexpl.io',
            'http_host' => 'code.apexpl.io',
            'svn_host' => 'svn.apexpl.io',
            'staging_host' => 'apexpl.dev',
            'name' => 'Apex Public Repository'
        ];

        // Save config.yml file
        file_put_contents("$dest/etc/.config.yml", Yaml::dump($config_yaml, 5));
    }

    /**
     * Help
     */
    public function help(Cli $cli):CliHelpScreen
    {

        $help = new CliHelpScreen(
            title: 'Compile Apex Core',
            usage: 'sys compile-core [--dest=]',
            description: "Never needs to be run, and is used by the maintainers of Apex to compile the base Github repository."
        );

        $help->addFlag('--dest', "Optional destination directory where to save Apex core to.  Defaults to the system's tmp directory.");
        $help->addExample('./apex sys compile-core');

        // Return
        return $help;
    }

}


