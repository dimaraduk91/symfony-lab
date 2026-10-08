import http from 'k6/http';
import { Counter } from 'k6/metrics';

const baseUrl = (__ENV.BASE_URL || 'http://localhost:8080').replace(/\/$/, '');
const orderId = __ENV.ORDER_ID || '01a0dd98-0ca1-728b-a209-9be1112bb332';

const status0 = new Counter('status_0');
const status2xx = new Counter('status_2xx');
const status3xx = new Counter('status_3xx');
const status4xx = new Counter('status_4xx');
const status5xx = new Counter('status_5xx');
const status500 = new Counter('status_500');
const status502 = new Counter('status_502');
const status503 = new Counter('status_503');
const status504 = new Counter('status_504');
const statusOther5xx = new Counter('status_other_5xx');

// export const options = {
//     vus: 500,
//     // stages: [
//     //     { duration: '10s', target: 50 },
//     //     { duration: '30s', target: 50 },
//     //     { duration: '5s', target: 0 },
//     // ],
//     // duration: '60s',
//     // duration: '1m',
//     // stages: [
//     //     { duration: '30s', target: 100 },
//     //     { duration: '30s', target: 200 },
//     //     { duration: '30s', target: 300 },
//     //     { duration: '30s', target: 500 },
//     //     { duration: '30s', target: 0 },
//     // ],
//     duration: '2m',
//
//     discardResponseBodies: true,
//
//     summaryTrendStats: [
//         'avg',
//         'min',
//         'med',
//         'p(90)',
//         'p(95)',
//         'p(99)',
//         'max',
//     ],
// };

export const options = {
    scenarios: {
        load: {
            executor: 'constant-arrival-rate',

            rate: Number(__ENV.RATE || 3500),
            timeUnit: '1s',

            duration: __ENV.DURATION || '30s',
            // duration: __ENV.DURATION || '2m',

            preAllocatedVUs: Number(__ENV.PRE_ALLOCATED_VUS || 500),
            maxVUs: Number(__ENV.MAX_VUS || 1000),
        },
    },

    discardResponseBodies: true,

    summaryTrendStats: [
        'avg',
        'min',
        'med',
        'p(90)',
        'p(95)',
        'p(99)',
        'max',
    ],
};

export default function () {
    const res = http.get(
        `${baseUrl}/api/orders/${orderId}`
    );

    if (res.status === 0) {
        status0.add(1);
    } else if (res.status >= 200 && res.status < 300) {
        status2xx.add(1);
    } else if (res.status >= 300 && res.status < 400) {
        status3xx.add(1);
    } else if (res.status >= 400 && res.status < 500) {
        status4xx.add(1);
    } else if (res.status === 500) {
        status500.add(1);
    } else if (res.status === 502) {
        status502.add(1);
    } else if (res.status === 503) {
        status503.add(1);
    } else if (res.status === 504) {
        status504.add(1);
    } else if (res.status >= 500) {
        statusOther5xx.add(1);
    } else {
        status5xx.add(1);
    }
}
