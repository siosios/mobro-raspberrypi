<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>MoBro Setup</title>
  <meta name="viewport" content="width=device-width, initial-scale=1"/>
  <link rel="shortcut icon" href="../resources/favicon.ico" type="image/x-icon"/>

  <link href="../vendor/bootstrap.min.css" rel="stylesheet"/>
  <link href="../vendor/fontawesome-free-5.13.0-web/css/all.min.css" rel="stylesheet"/>

  <style>

    .form-label {
      /*color: #f30;*/
      font-weight: bold;
    }

    .confirmation-header {
      font-weight: bold;
      margin-bottom: 0.5em;
    }

    .confirmation-title {
      color: dimgrey;
    }

    .multisteps-form__progress {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(0, 1fr));
    }

    .multisteps-form__progress-btn {
      transition-property: all;
      transition-duration: 0.15s;
      transition-timing-function: linear;
      transition-delay: 0s;
      position: relative;
      padding-top: 20px;
      color: rgba(108, 117, 125, 0.7);
      text-indent: -9999px;
      border: none;
      background-color: transparent;
      outline: none !important;
      cursor: pointer;
    }

    @media (min-width: 500px) {
      .multisteps-form__progress-btn {
        text-indent: 0;
      }
    }

    .multisteps-form__progress-btn:before {
      position: absolute;
      top: 0;
      left: 50%;
      display: block;
      width: 13px;
      height: 13px;
      content: '';
      -webkit-transform: translateX(-50%);
      transform: translateX(-50%);
      transition: all 0.15s linear 0s, -webkit-transform 0.15s cubic-bezier(0.05, 1.09, 0.16, 1.4) 0s;
      transition: all 0.15s linear 0s, transform 0.15s cubic-bezier(0.05, 1.09, 0.16, 1.4) 0s;
      transition: all 0.15s linear 0s, transform 0.15s cubic-bezier(0.05, 1.09, 0.16, 1.4) 0s, -webkit-transform 0.15s cubic-bezier(0.05, 1.09, 0.16, 1.4) 0s;
      border: 2px solid currentColor;
      border-radius: 50%;
      background-color: #fff;
      box-sizing: border-box;
      z-index: 3;
    }

    .multisteps-form__progress-btn:after {
      position: absolute;
      top: 5px;
      left: calc(-50% - 13px / 2);
      transition-property: all;
      transition-duration: 0.15s;
      transition-timing-function: linear;
      transition-delay: 0s;
      display: block;
      width: 100%;
      height: 2px;
      content: '';
      background-color: currentColor;
      z-index: 1;
    }

    .multisteps-form__progress-btn:first-child:after {
      display: none;
    }

    .multisteps-form__progress-btn.js-active {
      color: #f30;
    }

    .multisteps-form__progress-btn.js-active:before {
      -webkit-transform: translateX(-50%) scale(1.2);
      transform: translateX(-50%) scale(1.2);
      background-color: currentColor;
    }

    .multisteps-form__form {
      position: relative;
    }

    .multisteps-form__panel {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 0;
      opacity: 0;
      visibility: hidden;
    }

    .multisteps-form__panel.js-active {
      height: auto;
      opacity: 1;
      visibility: visible;
    }
  </style>

  <script src="../vendor/jquery-3.3.1.slim.min.js"></script>
  <script src="../vendor/bootstrap.bundle.min.js"></script>

</head>

<body>

<?php

include '../constants.php';

function getIfNotEof($file, $default)
{
    return $file && !feof($file) ? fgets($file) : $default;
}

function closeFile($file)
{
    if ($file) {
        fclose($file);
    }
}

function getDriverScripts($dir)
{
    $result = array();
    foreach (scandir($dir) as $key => $value) {
        if (!is_dir(Constants::DRIVER_GOODTFT_DIR . DIRECTORY_SEPARATOR . $value)) {
            if (fnmatch('*show', $value)) {
                $result[] = $value;
            }
        }
    }
    return $result;
}

$eth = shell_exec('grep up /sys/class/net/*/operstate | grep eth0');
$ethConnected = isset($eth) && trim($eth) !== '';

$ssid = shell_exec('iwgetid wlan0 -r');
$wlanConnected = isset($ssid) && trim($ssid) !== '';

$connected = $ethConnected || $wlanConnected;
$connectionMode = $ethConnected ? 'eth' : 'wifi';

$file = fopen(Constants::DISCOVERY_FILE, "r");
$storedDiscoveryMode = getIfNotEof($file, 'auto');
$storedKey = getIfNotEof($file, 'mobro');
$storedIp = getIfNotEof($file, '');
closeFile($file);

$file = fopen(Constants::VERSION_FILE, "r");
$storedVersion = getIfNotEof($file, 'Unknown');
closeFile($file);

$file = fopen(Constants::WIFI_FILE, "r");
$storedSsid = getIfNotEof($file, '');
$storedPw = getIfNotEof($file, '');
$storedCountry = getIfNotEof($file, 'AT');
$storedHidden = getIfNotEof($file, '0');
$storedWpa = getIfNotEof($file, '');
closeFile($file);

$storedSsIds = array();
$file = fopen(Constants::SSID_FILE, "r");
while ($file && !feof($file)) {
    $item = fgets($file);
    if (!empty(trim($item))) {
        $storedSsIds[] = $item;
    }
}
closeFile($file);

$drivers = array_merge(
    getDriverScripts(Constants::DRIVER_GOODTFT_DIR),
    getDriverScripts(Constants::DRIVER_WAVESHARE_DIR)
);


?>

<div class="container">
  <div class="multisteps-form mt-5">
    <!--progress bar-->
    <div class="row">
      <div class="col-12 col-lg-8 ml-auto mr-auto mb-4">
        <div class="multisteps-form__progress">
          <button class="multisteps-form__progress-btn js-active" type="button" title="User Info">Network</button>
          <button class="multisteps-form__progress-btn" type="button" title="Address">PC</button>
          <button class="multisteps-form__progress-btn" type="button" title="Order Info">Screen</button>
          <button class="multisteps-form__progress-btn" type="button" title="Comments">Confirmation</button>
        </div>
      </div>
    </div>
    <!--form panels-->
    <div class="row">
      <div class="col-12 col-lg-8 m-auto">
        <form class="multisteps-form__form" action="save.php" method="POST">

          <!--single form panel-->
          <div class="multisteps-form__panel shadow p-4 rounded bg-white js-active" data-animation="scaleIn">
            <h3 class="multisteps-form__title">Network configuration</h3>
            <div class="multisteps-form__content">
              <div class="form-row mt-4">
                <div class="col-2 font-weight-bold">Mode:</div>
                <div class="col">
                    <?php
                    if ($connectionMode == 'eth') {
                        echo '<span><i class="fas fa-network-wired"></i></span> Ethernet';
                    } else {
                        echo '<span><i class="fas fa-wifi"></i></span> Wireless';
                    }
                    ?>
                </div>
                <input type="hidden" id="networkModeInput" name="networkMode" value="<?php echo $connectionMode ?>">
              </div>
              <div class="form-row mt-4">
                <div class="col">
                  <label class="form-check-label form-label" for="ssidInput">
                    Wireless network name (SSID)
                  </label>
                  <div class="input-group">
                    <div class="input-group-prepend">
                      <span class="input-group-text">
                        <i class="fas fa-wifi"></i>
                      </span>
                    </div>
                    <input list="ssids" class="form-control" name="ssid" id="ssidInput"
                           aria-describedby="ssidHelp" value="<?php echo $storedSsid ?>"
                        <?php if ($connectionMode == 'eth') echo 'disabled' ?>
                    >
                    <datalist id="ssids">
                        <?php
                        foreach ($storedSsIds as $ssid) {
                            echo '<option value="' . $ssid . '">' . $ssid . '</option>';
                        }
                        ?>
                    </datalist>
                  </div>
                  <small id="ssidHelp" class="form-text text-muted">
                    The network name (SSID) of the wireless network to connect to.
                  </small>
                </div>
              </div>

              <div class="form-row mt-2">
                <div class="col">
                  <label class="form-check-label form-label" for="passwordInput">
                    Wireless network password
                  </label>
                  <div class="input-group">
                    <div class="input-group-prepend">
                      <span class="input-group-text">
                        <i class="fas fa-key"></i>
                      </span>
                    </div>
                    <input type="password" name="pw" class="form-control" id="passwordInput"
                           aria-describedby="pwHelp" value="<?php echo $storedPw ?>"
                        <?php if ($connectionMode == 'eth') echo 'disabled' ?>
                    >
                  </div>
                  <small id="pwHelp" class="form-text text-muted">
                    The password needed to connect to the selected wireless network.
                  </small>
                </div>
              </div>

              <div class="form-row mt-2">
                <div class="col">
                  <label class="form-check-label form-label" for="countryInput">
                    Country
                  </label>
                  <div class="input-group">
                    <div class="input-group-prepend">
                      <span class="input-group-text">
                        <i class="fas fa-globe-europe"></i>
                      </span>
                    </div>
                    <select id="countryInput" name="country" class="form-control" aria-describedby="countryInputHelp"
                        <?php if ($connectionMode == 'eth') echo 'disabled' ?>
                    >
                        <?php
                        if (($handle = fopen("../resources/country_codes.csv", "r")) !== FALSE) {
                            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                                $selected = $data[1] == $storedCountry ? 'selected="selected"' : '';
                                echo '<option value="' . $data[1] . '" ' . $selected . '>' . $data[0] . '</option>';
                            }
                            fclose($handle);
                        }
                        ?>
                    </select>
                  </div>
                  <small id="countryInputHelp" class="form-text text-muted">
                    The country in which the device is being used. <br>
                    This is needed so the 5G wireless networking can choose the correct frequency bands.
                  </small>
                </div>
              </div>

              <div class="form-row mt-1">
                <button class="btn btn-link ml-auto" type="button" data-toggle="collapse"
                        data-target="#networkAdvancedCollapse"
                        aria-expanded="false" aria-controls="networkAdvancedCollapse">
                  Advanced settings
                </button>
              </div>

              <div class="collapse" id="networkAdvancedCollapse">

                <div class="form-row mt-2">
                  <div class="col">
                    <label class="form-check-label form-label" for="wpaInput">
                      Security & Encryption standard
                    </label>
                    <div class="input-group">
                      <div class="input-group-prepend">
                      <span class="input-group-text">
                        <i class="fas fa-lock"></i>
                      </span>
                      </div>
                      <select id="wpaInput" name="wpa" class="form-control" aria-describedby="wpaInputHelp"
                          <?php if ($connectionMode == 'eth') echo 'disabled' ?>
                      >
                        <option value="" <?php if (empty($storedWpa)) echo 'selected="selected"' ?>>
                          Automatic
                        </option>
                        <option value="2a" <?php if ($storedWpa == '2a') echo 'selected="selected"' ?>>
                          WPA2-PSK (AES)
                        </option>
                        <option value="2t" <?php if ($storedWpa == '2t') echo 'selected="selected"' ?>>
                          WPA2-PSK (TKIP)
                        </option>
                        <option value="1t" <?php if ($storedWpa == '1t') echo 'selected="selected"' ?>>
                          WPA-PSK (TKIP)
                        </option>
                        <option value="n" <?php if ($storedWpa == 'n') echo 'selected="selected"' ?>>
                          None (Unsecured network)
                        </option>
                      </select>
                    </div>
                    <small id="wpaInputHelp" class="form-text text-muted">
                      The WPA version and encryption method to use. <br>
                      Only change this if 'Automatic' does not work and/or your router requires a specific WPA standard
                      or encryption method.
                    </small>
                  </div>
                </div>

                <div class="form-row mt-3">
                  <div class="col">
                    <div class="form-check">
                      <input type="checkbox" class="form-check-input" id="hiddenNetworkInput" name="hidden"
                             aria-describedby="hiddenNetworkHelp" <?php if ($storedHidden == '1') echo 'checked' ?>
                          <?php if ($connectionMode == 'eth') echo 'disabled' ?>
                      >
                      <label class="form-check-label form-label" for="hiddenNetworkInput">
                        <span><i class="fas fa-ghost"></i></span> Hidden wireless network
                      </label>
                    </div>
                    <small id="hiddenNetworkHelp" class="form-text text-muted">
                      Check this if you configured your router to hide the wireless network name (SSID).
                    </small>
                  </div>
                </div>

              </div>

              <div class="button-row d-flex mt-4">
                <a href="index.php" class="btn btn-danger" role="button" title="Cancel">&#x1f5d9; Cancel</a>
                <button class="btn btn-primary ml-auto js-btn-next" type="button" title="Next">
                  Next &#x1f846;
                </button>
              </div>
            </div>
          </div>

          <!--single form panel-->
          <div class="multisteps-form__panel shadow p-4 rounded bg-white" data-animation="scaleIn">
            <h3 class="multisteps-form__title">Setup connection to PC</h3>
            <div class="multisteps-form__content">
              <div class="form-row mt-4">
                <div class="col">
                  <div class="form-check">
                    <input class="form-check-input" type="radio" name="discovery" id="discovery1" value="auto"
                        <?php if ($storedDiscoveryMode == 'auto') echo 'checked' ?>>
                    <label class="form-check-label" for="discovery1">
                      Automatic discovery using PC network name
                    </label>
                  </div>
                  <div class="form-check">
                    <input class="form-check-input" type="radio" name="discovery" id="discovery2" value="manual"
                        <?php if ($storedDiscoveryMode == 'manual') echo 'checked' ?>>
                    <label class="form-check-label" for="discovery2">
                      Manual IP address configuration
                    </label>
                  </div>
                </div>
              </div>

              <div class="form-row mt-3">
                <div class="col">
                  <label class="form-check-label form-label" for="connectionKeyInput">
                    PC network name
                  </label>
                  <div class="input-group">
                    <div class="input-group-prepend">
                      <span class="input-group-text">
                        <i class="fas fa-search"></i>
                      </span>
                    </div>
                    <input class="multisteps-form__input form-control border-primary" id="connectionKeyInput"
                           type="text" name="key"
                           value="<?php echo $storedKey ?>"
                           placeholder="mobro"
                           aria-describedby="connectionKeyHelp"
                    />
                  </div>
                  <small id="connectionKeyHelp" class="form-text text-muted">
                    The 'PC Network Name' as configured in the MoBro PC application. (default: mobro)
                  </small>
                </div>
              </div>

              <div class="form-row mt-2">
                <div class="col">
                  <label class="form-check-label form-label" for="staticIpInput">
                    Static IP address
                  </label>
                  <div class="input-group">
                    <div class="input-group-prepend">
                      <span class="input-group-text">
                        <i class="fas fa-at"></i>
                      </span>
                    </div>
                    <input class="multisteps-form__input form-control" id="staticIpInput" type="text" name="ip"
                           aria-describedby="staticIpHelp" disabled
                           value="<?php echo $storedIp ?>"
                    />
                  </div>
                  <small id="staticIpHelp" class="form-text text-muted">
                    The static IP address of the PC within the network. (e.g.: 192.168.0.12)
                  </small>
                </div>
              </div>
              <div class="button-row d-flex mt-4">
                <button class="btn btn-primary js-btn-prev" type="button" title="Prev">&#x1f844; Prev</button>
                <button class="btn btn-primary ml-auto js-btn-next" type="button" title="Next">Next &#x1f846;</button>
              </div>
            </div>
          </div>

          <!--single form panel-->
          <div class="multisteps-form__panel shadow p-4 rounded bg-white" data-animation="scaleIn">
            <h3 class="multisteps-form__title">Screen configuration</h3>
            <div class="multisteps-form__content">
              <div class="form-row mt-4">
                <div class="col">
                  <div class="form-check">
                    <input class="form-check-input" type="radio" name="screen" id="screen1" value="skip" checked>
                    <label class="form-check-label" for="screen1">
                      Skip driver installation
                    </label>
                    <small id="screen1" class="form-text text-muted">
                      <ul>
                        <li>Connected via HDMI</li>
                        <li>Already installed, display is working</li>
                        <li>I'll install the required drivers manually myself</li>
                      </ul>
                    </small>
                  </div>
                  <div class="form-check">
                    <input class="form-check-input" type="radio" name="screen" id="screen2" value="install">
                    <label class="form-check-label" for="screen2">
                      Install the selected display driver below &#x2b0e;
                    </label>
                  </div>
                </div>
              </div>
              <div class="form-row mt-2">
                <div class="col">
                  <label class="form-check-label form-label" for="driverInput">
                    Display driver selection
                  </label>
                  <div class="input-group">
                    <div class="input-group-prepend">
                      <span class="input-group-text">
                        <i class="fas fa-desktop"></i>
                      </span>
                    </div>
                    <select id="driverInput" name="driver" class="form-control" aria-describedby="staticIpHelp"
                            disabled>
                      <option value="" selected>No driver selected</option>
                        <?php
                        foreach ($drivers as $driver) {
                            echo '<option value="' . $driver . '">' . $driver . '</option>';
                        }
                        ?>
                    </select>
                  </div>
                  <small id="driverInputHelp" class="form-text text-muted">
                    Check your display and select the corresponding driver from the list
                  </small>
                </div>
              </div>

              <div class="button-row d-flex mt-4">
                <button class="btn btn-primary js-btn-prev" type="button" title="Prev">&#x1f844; Prev</button>
                <button class="btn btn-primary ml-auto js-btn-next" type="button" title="Next">Next &#x1f846;</button>
              </div>
            </div>
          </div>

          <!--single form panel-->
          <div class="multisteps-form__panel shadow p-4 rounded bg-white" data-animation="scaleIn">
            <h3 class="multisteps-form__title">New Configuration</h3>
            <div class="multisteps-form__content">

              <div class="form-row mt-4 confirmation-header">Network</div>
              <div class="form-row">
                <div class="col-1"></div>
                <div class="col-4 confirmation-title">Mode</div>
                <div class="col" id="summaryNetworkMode">
                    <?php echo $connectionMode == 'eth' ? 'Ethernet' : 'Wireless' ?>
                </div>
              </div>
              <div class="form-row">
                <div class="col-1"><span><i class="fas fa-wifi"></i></span></div>
                <div class="col-4 confirmation-title">SSID</div>
                <div class="col" id="summarySSID">
                    <?php echo $connectionMode == 'eth' ? '&#x1f5d9;' : '' ?>
                </div>
              </div>
              <div class="form-row">
                <div class="col-1"><span><i class="fas fa-key"></i></span></div>
                <div class="col-4 confirmation-title">Password</div>
                <div class="col" id="summaryPW">
                    <?php echo $connectionMode == 'eth' ? '&#x1f5d9;' : '' ?>
                </div>
              </div>
              <div class="form-row">
                <div class="col-1"><span><i class="fas fa-globe-europe"></i></span></div>
                <div class="col-4 confirmation-title">Country</div>
                <div class="col" id="summaryCountry">
                    <?php echo $connectionMode == 'eth' ? '&#x1f5d9;' : '' ?>
                </div>
              </div>
              <div class="form-row">
                <div class="col-1"><span><i class="fas fa-lock"></i></span></div>
                <div class="col-4 confirmation-title">Standard</div>
                <div class="col" id="summarySecurity">
                    <?php echo $connectionMode == 'eth' ? '&#x1f5d9;' : '' ?>
                </div>
              </div>
              <div class="form-row">
                <div class="col-1"><span><i class="fas fa-ghost"></i></span></div>
                <div class="col-4 confirmation-title">Hidden network</div>
                <div class="col" id="summaryHiddenNet">
                    <?php echo $connectionMode == 'eth' ? '&#x1f5d9;' : '' ?>
                </div>
              </div>
              <hr>
              <div class="form-row mt-2 confirmation-header">PC Connection</div>
              <div class="form-row">
                <div class="col-1"></div>
                <div class="col-4 confirmation-title">Mode</div>
                <div class="col" id="summaryPcConnMode"></div>
              </div>
              <div class="form-row" id="summaryConKeyRow">
                <div class="col-1"><span><i class="fas fa-search"></i></span></div>
                <div class="col-4 confirmation-title">PC network name</div>
                <div class="col" id="summaryConKey"></div>
              </div>
              <div class="form-row" id="summaryIpRow">
                <div class="col-1"><span><i class="fas fa-at"></i></span></div>
                <div class="col-4 confirmation-title">IP address</div>
                <div class="col" id="summaryIp"></div>
              </div>
              <hr>
              <div class="form-row mt-2 confirmation-header">Screen</div>
              <div class="form-row">
                <div class="col-1"></div>
                <div class="col-4 confirmation-title">Mode</div>
                <div class="col" id="summaryScreenMode">Skip driver installation</div>
              </div>
              <div class="form-row">
                <div class="col-1"><span><i class="fas fa-desktop"></i></span></div>
                <div class="col-4 confirmation-title">Driver</div>
                <div class="col" id="summaryDriver">&#x1f5d9;</div>
              </div>

              <div class="row mt-4 alert alert-info">
                Note: Upon applying the new configuration the Raspberry Pi will reboot.
              </div>
              <div class="button-row d-flex mt-4">
                <button class="btn btn-primary js-btn-prev" type="button" title="Prev">&#x1f844; Prev</button>
                <a href="index.php" class="btn btn-danger ml-auto" role="button" title="Cancel">&#x1f5d9; Cancel</a>
                <button class="btn btn-success ml-4" type="submit" title="Apply">&#x2713; Apply</button>
              </div>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<script>
  //DOM elements
  const DOMstrings = {
    stepsBtnClass: 'multisteps-form__progress-btn',
    stepsBtns: document.querySelectorAll(`.multisteps-form__progress-btn`),
    stepsBar: document.querySelector('.multisteps-form__progress'),
    stepsForm: document.querySelector('.multisteps-form__form'),
    stepsFormTextareas: document.querySelectorAll('.multisteps-form__textarea'),
    stepFormPanelClass: 'multisteps-form__panel',
    stepFormPanels: document.querySelectorAll('.multisteps-form__panel'),
    stepPrevBtnClass: 'js-btn-prev',
    stepNextBtnClass: 'js-btn-next'
  };

  //remove class from a set of items
  const removeClasses = (elemSet, className) => {
    elemSet.forEach(elem => {
      elem.classList.remove(className);
    });
  };

  //return exect parent node of the element
  const findParent = (elem, parentClass) => {
    let currentNode = elem;
    while (!currentNode.classList.contains(parentClass)) {
      currentNode = currentNode.parentNode;
    }
    return currentNode;
  };

  //get active button step number
  const getActiveStep = elem => {
    return Array.from(DOMstrings.stepsBtns).indexOf(elem);
  };

  //set all steps before clicked (and clicked too) to active
  const setActiveStep = activeStepNum => {

    //remove active state from all the state
    removeClasses(DOMstrings.stepsBtns, 'js-active');

    //set picked items to active
    DOMstrings.stepsBtns.forEach((elem, index) => {
      if (index <= activeStepNum) {
        elem.classList.add('js-active');
      }
    });
  };

  //get active panel
  const getActivePanel = () => {
    let activePanel;
    DOMstrings.stepFormPanels.forEach(elem => {
      if (elem.classList.contains('js-active')) {
        activePanel = elem;
      }
    });
    return activePanel;
  };

  //open active panel (and close unactive panels)
  const setActivePanel = activePanelNum => {

    //remove active class from all the panels
    removeClasses(DOMstrings.stepFormPanels, 'js-active');

    //show active panel
    DOMstrings.stepFormPanels.forEach((elem, index) => {
      if (index === activePanelNum) {
        elem.classList.add('js-active');
        setFormHeight(elem);
      }
    });
  };

  //set form height equal to current panel height
  const formHeight = activePanel => {
    const activePanelHeight = activePanel.offsetHeight;
    DOMstrings.stepsForm.style.height = `${activePanelHeight}px`;
  };

  const setFormHeight = () => {
    const activePanel = getActivePanel();
    formHeight(activePanel);
  };

  //STEPS BAR CLICK FUNCTION
  DOMstrings.stepsBar.addEventListener('click', e => {

    //check if click target is a step button
    const eventTarget = e.target;
    if (!eventTarget.classList.contains(`${DOMstrings.stepsBtnClass}`)) {
      return;
    }

    //get active button step number
    const activeStep = getActiveStep(eventTarget);

    //set all steps before clicked (and clicked too) to active
    setActiveStep(activeStep);

    //open active panel
    setActivePanel(activeStep);
  });

  //PREV/NEXT BTNS CLICK
  DOMstrings.stepsForm.addEventListener('click', e => {

    const eventTarget = e.target;

    //check if we clicked on `PREV` or NEXT` buttons
    if (!(eventTarget.classList.contains(`${DOMstrings.stepPrevBtnClass}`) || eventTarget.classList.contains(`${DOMstrings.stepNextBtnClass}`))) {
      return;
    }

    //find active panel
    const activePanel = findParent(eventTarget, `${DOMstrings.stepFormPanelClass}`);
    let activePanelNum = Array.from(DOMstrings.stepFormPanels).indexOf(activePanel);

    //set active step and active panel onclick
    if (eventTarget.classList.contains(`${DOMstrings.stepPrevBtnClass}`)) {
      activePanelNum--;
    } else {
      activePanelNum++;
    }
    setActiveStep(activePanelNum);
    setActivePanel(activePanelNum);
  });

  //SETTING PROPER FORM HEIGHT ONLOAD
  window.addEventListener('load', setFormHeight, false);

  //SETTING PROPER FORM HEIGHT ONRESIZE
  window.addEventListener('resize', setFormHeight, false);

  $(document).ready(function () {

    // summary fields
    let summaryPcConnMode = $('#summaryPcConnMode');
    let summaryConKey = $('#summaryConKey');
    let summaryIp = $('#summaryIp');
    let summaryScreenMode = $('#summaryScreenMode');

    // network
      <?php
      if ($connectionMode == 'wifi') {
          echo "
            $('#summarySSID').html($('#ssidInput').val());
            $('#summaryPW').html(\"*\".repeat($('#passwordInput').val().length));
            $('#summaryCountry').html($('#countryInput option:selected').text());
            $('#summarySecurity').html($('#wpaInput option:selected').text());
            $('#summaryHiddenNet').html($('#hiddenNetworkInput').prop('checked') ? 'Yes' : 'No');
        ";
      }
      ?>

    summaryPcConnMode.html($('#discovery1').prop('checked') ? 'Automatic discovery' : 'Static IP');
    summaryConKey.html($('#discovery1').prop('checked') ? $('#connectionKeyInput').val() : '&#x1f5d9;');
    summaryIp.html($('#discovery1').prop('checked') ? '&#x1f5d9;' : $('#staticIpInput').val());

    $('#ssidInput').on('change', _ => $('#summarySSID').html($('#ssidInput').val()));
    $('#passwordInput').on('change', _ => $('#summaryPW').html("*".repeat($('#passwordInput').val().length)));
    $('#countryInput').on('change', _ => $('#summaryCountry').html($('#countryInput option:selected').text()));
    $('#wpaInput').on('change', _ => $('#summarySecurity').html($('#wpaInput option:selected').text()));
    $('#hiddenNetworkInput').on('change', _ => $('#summaryHiddenNet').html($('#hiddenNetworkInput').prop('checked') ? 'Yes' : 'No'));

    // PC config toggle
    let ipInput = $('#staticIpInput');
    let connKeyInput = $('#connectionKeyInput');
    $('#discovery1').on('click', _ => {
      ipInput.attr('disabled', 'disabled');
      ipInput.removeClass('border-primary');
      connKeyInput.removeAttr('disabled');
      connKeyInput.addClass('border-primary');
      summaryPcConnMode.html('Automatic discovery');
      summaryIp.html('&#x1f5d9;');
      summaryConKey.html($('#connectionKeyInput').val());
    });
    $('#discovery2').on('click', _ => {
      connKeyInput.attr('disabled', 'disabled');
      connKeyInput.removeClass('border-primary');
      ipInput.removeAttr('disabled');
      ipInput.addClass('border-primary');
      summaryPcConnMode.html('Static IP');
      summaryConKey.html('&#x1f5d9;');
      summaryIp.html($('#staticIpInput').val());
    });
    $('#staticIpInput').on('change', _ => summaryIp.html($('#staticIpInput').val()));
    $('#connectionKeyInput').on('change', _ => summaryConKey.html($('#connectionKeyInput').val()));

    // driver install toggle
    let driverInput = $('#driverInput');
    driverInput.on('change', _ => $('#summaryDriver').html($('#driverInput option:selected').text()));
    $('#screen1').on('click', _ => {
      driverInput.attr('disabled', 'disabled');
      driverInput.removeClass('border-primary');
      summaryScreenMode.html('Skip driver installation');
    });
    $('#screen2').on('click', _ => {
      driverInput.removeAttr('disabled');
      driverInput.addClass('border-primary');
      summaryScreenMode.html('Install driver');
    });
  });

</script>
</body>
</html>
