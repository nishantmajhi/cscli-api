/* Unit 8: Structure and Union
 * Passing a structure to a function, and passing an array of
 * structures to a function. */
#include <stdio.h>

struct Point {
    int x, y;
};

void printPoint(struct Point p) { /* passing structure to function */
    printf("(%d, %d)\n", p.x, p.y);
}

float averageX(struct Point pts[], int n) { /* array of structures */
    int sum = 0;
    for (int i = 0; i < n; i++) {
        sum += pts[i].x;
    }
    return (float)sum / n;
}

int main(void) {
    struct Point p1 = {3, 4};
    printPoint(p1);

    struct Point pts[3] = {{1, 1}, {2, 2}, {3, 3}};
    printf("Average x = %.2f\n", averageX(pts, 3));

    return 0;
}
