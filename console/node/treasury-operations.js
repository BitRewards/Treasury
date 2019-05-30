const process = require('process');
const fs = require('fs');

require('dotenv').config();

const web3_http = process.env['NODE_URL'];
if (!web3_http) {
    throw "Environment variable NODE_URL should contain Ethereum node url";
}

const Web3 = require('web3');
const web3 = new Web3(
    new Web3.providers.HttpProvider(web3_http)
);
const keyFile = process.env['ETH_MASTER_KEY_FILE'];
if (!keyFile) {
    throw "Environment variable ETH_MASTER_KEY_FILE should contain the path to the file with Ethereum master private key (EthereumBip44)";
}

if (!fs.existsSync(keyFile)) {
    throw `${keyFile} does not exist (read from ETH_MASTER_KEY_FILE)`;
}

const Tx = require('ethereumjs-tx');
const EthereumBip44 = require('ethereum-bip44');
const ETH_MASTER_KEY = new EthereumBip44.fromPrivateSeed(
    fs.readFileSync(keyFile).toString()
);

const TOKEN_NAME = process.env['TOKEN_NAME'];
if (!TOKEN_NAME) {
    throw "Environment variable TOKEN_NAME should contain valid token name";
}

let operations = {
    getAddress(index) {
        return ETH_MASTER_KEY.getAddress(index);

    },

    getPrivateKey(index) {
        return ETH_MASTER_KEY.getPrivateKey(index).toString('hex');
    },

    async getEthBalance(address) {
        return await web3.eth.getBalance(address)
    },

    async getTokenBalance(address) {
        try {
            const abi = fs.readFileSync('tokens/'+TOKEN_NAME+'/abi.json').toString();
            const contractAddress = fs.readFileSync('tokens/'+TOKEN_NAME+'/address').toString();

            const tokenContract = new web3.eth.Contract(JSON.parse(abi), contractAddress);

            return await tokenContract.methods.balanceOf(address).call();

        } catch (e) {
            throw e.message;
        }
    },

    async getBlockNumber() {
        return await web3.eth.getBlockNumber();
    },

    async getTransaction(hash) {
        const receiptData = await web3.eth.getTransactionReceipt(hash);
        const txData = await web3.eth.getTransaction(hash);

        return {
            tx: hash,
            blockNumber: receiptData.blockNumber,
            eth_fee: receiptData.gasUsed * txData.gasPrice,
            gas_limit: txData.gas,
            gas_price: txData.gasPrice,
            receipt: receiptData,
            txData: txData
        };
    },

    async getBlockTransactions(index) {
        const block = await web3.eth.getBlock(index, true);

        const blockTransactions = block.transactions || []
        let transactions = []

        for (let tx of blockTransactions) {
            // const txDetails = await operations.getTransaction(hash)

            tx.amount = web3.utils.fromWei(tx.value)

            if (tx.value !== '0') {
				transactions.push(tx)
			}
		}

        return transactions
    },

    async getBlockEvents(index) {

    },

    async getSingleBlockLogs(block) {
        return await operations.getLogs(block, block);
    },

    async getWalletTransactions(index) {
        const address = operations.getAddress(index);

        const options = {
            address: address,
        };

        const logs = await web3.eth.getPastLogs(options);
        console.log(logs);

        return logs;
    },

    async getLogs(fromBlock, toBlock, event) {
        const eventTransfer = web3.utils.sha3("Transfer(address,address,uint256)");
        const eventBITTransfer = web3.utils.sha3("BITTransfer(address,address,uint256,bytes32)");

        const events = event && event === 'BITTransfer' ? [eventBITTransfer] : [eventTransfer];
        // const events = [eventBITTransfer];

        const abi = JSON.parse(fs.readFileSync('tokens/'+TOKEN_NAME+'/abi.json'));
        const contractAddress = fs.readFileSync('tokens/'+TOKEN_NAME+'/address').toString();

        const options = {
            fromBlock: web3.utils.numberToHex(fromBlock),
            toBlock: web3.utils.numberToHex(toBlock),
            address: contractAddress,
            topics: events
        };

        const logs = await web3.eth.getPastLogs(options);
        let transactions = [];
        for (let i = 0; i < logs.length; i++) {
            let item = logs[i];

            if (!events.includes(item.topics[0])) {
                continue;
            }

            const from = item.topics[1] || false;
            const to = item.topics[2] || false;

            const txDetails = await operations.getTransaction(item.transactionHash);

            let txData = {};

            if (item.topics[0] === eventBITTransfer) {
                const bytes = web3.utils.hexToBytes(item.data);
                const amountData = web3.utils.bytesToHex(bytes.slice(0, 32));
                const extraData = web3.utils.bytesToHex(bytes.slice(32, 64));

                txData = {
                    from: txDetails.receipt.from,
                    to: web3.utils.padLeft(web3.utils.numberToHex(web3.utils.toBN(to)), 40),
                    amount: web3.utils.fromWei(amountData),
                    extra_data: web3.utils.hexToString(extraData)
                }
            } else {
                txData = {
                    from: txDetails.receipt.from,
                    to: web3.utils.padLeft(web3.utils.numberToHex(web3.utils.toBN(to)), 40),
                    amount: web3.utils.fromWei(item.data),
                }
            }

            const tx = Object.assign({}, txDetails, txData);

            transactions.push(tx);
        }

        return transactions;
    },

    async getGasPrice() {
        return await web3.eth.getGasPrice();
    },


    async getNonce(address) {
        return await web3.eth.getTransactionCount(address);
    },

    async sendEther(senderIndex, toAddress, amountInWei) {
        const address = operations.getAddress(senderIndex);
        const nonce = await operations.getNonce(address);
        const rawTx = {
            nonce: web3.utils.numberToHex(nonce + ""),
            gasPrice: web3.utils.numberToHex(await operations.getGasPrice()),
            gasLimit: web3.utils.numberToHex(100000),
            to: toAddress + "",
            value: web3.utils.numberToHex(amountInWei + ""),
            // data: "0x..."
        };

        const privateKey = Buffer.from(operations.getPrivateKey(senderIndex), 'hex');

        const tx = new Tx(rawTx);
        tx.sign(privateKey);

        const serializedTx = tx.serialize();
        const hash = await new Promise((resolve, reject) => {
            web3.eth.sendSignedTransaction('0x' + serializedTx.toString('hex'))
                .once('transactionHash', function (hash) {
                    resolve(hash);
                })
                .on('error', (error) => {
                    reject(error.message)
                });
        });

        return hash;
    },

    async sendToken(senderIndex, toAddress, amountInWei, data = null) {
        const abi = JSON.parse(fs.readFileSync('tokens/'+TOKEN_NAME+'/abi.json'));
        const contractAddress = fs.readFileSync('tokens/'+TOKEN_NAME+'/address').toString();

        const address = operations.getAddress(senderIndex);

        const tokenContract = new web3.eth.Contract(abi, contractAddress, {from: address});

        const nonce = await operations.getNonce(address);

        const transferMethod = data
            ? tokenContract.methods.transfer(toAddress, web3.utils.numberToHex(amountInWei), web3.utils.fromAscii(data)).encodeABI()
            : tokenContract.methods.transfer(toAddress, web3.utils.numberToHex(amountInWei)).encodeABI();

        const rawTx = {
            from: address,
            nonce: web3.utils.numberToHex(nonce + ""),
            gasPrice: web3.utils.numberToHex(await operations.getGasPrice()),
            gasLimit: web3.utils.numberToHex(210000),
            to: contractAddress,
            value: '0x0',
            data: transferMethod
        };

        const privateKey = Buffer.from(operations.getPrivateKey(senderIndex), 'hex');

        const tx = new Tx(rawTx);
        tx.sign(privateKey);

        const serializedTx = tx.serialize();
        const hash = await new Promise((resolve, reject) => {
            web3.eth.sendSignedTransaction('0x' + serializedTx.toString('hex'))
                .once('transactionHash', function (hash) {
                    resolve(hash);
                })
                .on('error', (error) => {
                    reject(error.message)
                });
        });
        return hash;
    },

    async getTokenTransferEthFeeEstimate(senderIndex) {
        const abi = JSON.parse(fs.readFileSync('tokens/'+TOKEN_NAME+'/abi.json'));
        const contractAddress = fs.readFileSync('tokens/'+TOKEN_NAME+'/address').toString();
        const address = operations.getAddress(senderIndex);
        const tokenContract = new web3.eth.Contract(abi, contractAddress, {from: address});
        const toAddress = contractAddress;

        const amountInWei = web3.utils.toWei('1');

        const rawTx = {
            from: address,
            gas: web3.utils.numberToHex(210000),
            value: '0x0',
            // data: tokenContract.methods.transfer(toAddress, amountInWei).encodeABI()
        };

        const gasPrice = await operations.getGasPrice();
        const eth_fee = await tokenContract.methods.transfer(toAddress, amountInWei).estimateGas(rawTx) * gasPrice;

        return web3.utils.fromWei(eth_fee + "");
    },

    async getEthTransferFeeEstimate(senderIndex) {
        const address = operations.getAddress(senderIndex);

        const rawTx = {
            to: address, // use same as from
            gas: web3.utils.numberToHex(210000),
        };

        const gasPrice = await operations.getGasPrice();
        const estimatedGas = await web3.eth.estimateGas(rawTx);
        const eth_fee = estimatedGas * gasPrice;

        return web3.utils.fromWei(eth_fee + "");
    },
};
(async () => {
    const operation = process.argv[2];
    if (!operation) {
        throw "Usage: treasury-operations.js OPERATION PARAMS..."
    }
    if (!operations[operation]) {
        throw `Unknown operation ${operation}`;
    }

    let walletIndex, address, blockNumber, senderIndex, toAddress, amountInWei, hash;

    switch (operation) {
        case 'getAddress':
            walletIndex = parseInt(process.argv[3]);
            console.log(
                operations.getAddress(walletIndex)
            );
            break;
        case 'getPrivateKey':
            walletIndex = parseInt(process.argv[3]);
            console.log(
                operations.getPrivateKey(walletIndex)
            );
            break;
        case 'getEthBalance':
            address = process.argv[3];
            console.log(
                await operations.getEthBalance(address)
        );
            break;
        case 'getTokenBalance':
            address = process.argv[3];
            console.log(
                await operations.getTokenBalance(address)
            );
            break;
        case 'getTransaction':
            hash = process.argv[3];
            console.log(JSON.stringify(await operations.getTransaction(hash)));
            break;
        case 'getBlockTransactions':
            blockNumber = parseInt(process.argv[3]);
            console.log(JSON.stringify(await operations.getBlockTransactions(blockNumber)));
            break;
        case 'getBlockEvents':
            blockNumber = parseInt(process.argv[3]);
            console.log(JSON.stringify(await operations.getBlockTransactions(blockNumber)));
            break;
        case 'getBlockNumber':
            console.log(await operations.getBlockNumber());
            break;
        case 'sendEther':
            senderIndex = parseInt(process.argv[3]);
            toAddress = process.argv[4];
            amountInWei = process.argv[5];

            console.log(await operations.sendEther(senderIndex, toAddress, amountInWei));
            break;
        case 'sendToken':
            senderIndex = parseInt(process.argv[3]);
            toAddress = process.argv[4];
            amountInWei = process.argv[5];
            data = process.argv[6];

            hash = await operations.sendToken(senderIndex, toAddress, amountInWei, data);
            console.log(hash);
            break;

        case 'getSingleBlockLogs':
            block = parseInt(process.argv[3]);
            data = await operations.getSingleBlockLogs(block);
            console.log(JSON.stringify(data));
            break;

        case 'getLogs':
            fromBlock = parseInt(process.argv[3]);
            toBlock = parseInt(process.argv[4]);
            event = process.argv[5];
            data = await operations.getLogs(fromBlock, toBlock, event);
            console.log(JSON.stringify(data));
            break;

        case 'getWalletTransactions':
            walletIndex = parseInt(process.argv[3]);
            data = await operations.getWalletTransactions(walletIndex);
            console.log(data);
            break;

        case 'getTokenTransferEthFeeEstimate':
            walletIndex = parseInt(process.argv[3]);
            data = await operations.getTokenTransferEthFeeEstimate(walletIndex);
            console.log(data);
            break;

        case 'getEthTransferFeeEstimate':
            walletIndex = parseInt(process.argv[3]);
            data = await operations.getEthTransferFeeEstimate(walletIndex);
            console.log(data);
            break;
    }
})().catch(function(reason) {
    console.log(reason);
    process.exit(1);
}).then(() => {
    process.exit(0);
});
